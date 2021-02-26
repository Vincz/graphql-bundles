<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle;

use Overblog\GraphQLBundle\Configuration\ArgumentConfiguration;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\EnumConfiguration;
use Overblog\GraphQLBundle\Configuration\EnumValueConfiguration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\FieldConfiguration;
use Overblog\GraphQLBundle\Configuration\InputConfiguration;
use Overblog\GraphQLBundle\Configuration\InputFieldConfiguration;
use Overblog\GraphQLBundle\Configuration\InterfaceConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\ScalarConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use Overblog\GraphQLBundle\Configuration\UnionConfiguration;
use Overblog\GraphQLBundle\ConfigurationProvider\ConfigurationFilesParser;
use Overblog\GraphQLBundle\Extension\Access\AccessExtension;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;
use Overblog\GraphQLBundle\Extension\IsPublic\IsPublicExtension;
use SplFileInfo;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use function sprintf;

class ConfigurationYamlParser extends ConfigurationFilesParser
{
    protected Parser $yamlParser;

    protected Configuration $configuration;

    public function __construct(array $directories = [])
    {
        parent::__construct($directories);
        $this->configuration = new Configuration();
    }

    public function getYamlParser()
    {
        if (!isset($this->yamlParser)) {
            $this->yamlParser = new Parser();
        }

        return $this->yamlParser;
    }

    public function getSupportedExtensions(): array
    {
        return ['yaml', 'yml'];
    }

    public function getConfiguration(): Configuration
    {
        $files = $this->getFiles();
        $config = [];
        foreach ($files as $file) {
            $config[] = $this->parseFile($file);
        }

        $config = (new Processor())->processConfiguration(new TypesConfiguration(), $config);

        // Turns the array into a proper configuration object
        $configuration = new Configuration();
        foreach ($config as $name => $typeConfig) {
            $typeConfiguration = $this->configArrayToTypeConfiguration($name, $typeConfig);
            if (null !== $typeConfiguration) {
                $configuration->addType($typeConfiguration);
            }
        }

        return $configuration;
    }

    protected function parseFile(SplFileInfo $file): array
    {
        try {
            $typesConfig = $this->getYamlParser()->parse(file_get_contents($file->getPathname()), Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new InvalidConfigurationException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return is_array($typesConfig) ? $typesConfig : [];
    }

    /**
     * Transform a type array config into a proper TypeConfiguration
     *
     * @return Overblog\GraphQLBundle\Configuration\TypeConfiguration|null
     */
    protected function configArrayToTypeConfiguration(string $name, array $typeConfig): ?TypeConfiguration
    {
        $config = $typeConfig['config'];
        $configType = $typeConfig['type'];

        switch ($configType) {
            case TypesConfiguration::TYPE_INTERFACE:
                $typeConfiguration = new InterfaceConfiguration($name);
                if (isset($config['resolveType'])) {
                    $typeConfiguration->setTypeResolver($config['resolveType']);
                }
                // no break
            case TypesConfiguration::TYPE_OBJECT:
                $typeConfiguration = $typeConfiguration ?? new ObjectConfiguration($name);
                if (TypesConfiguration::TYPE_OBJECT === $configType && count($config['interfaces']) > 0) {
                    $typeConfiguration->setInterfaces($config['interfaces']);
                }
                if (isset($config['builders'])) {
                    foreach ($config['builders'] as $fieldsBuilder) {
                        $typeConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
                            'type' => BuilderExtension::TYPE_FIELDS,
                            'name' => $fieldsBuilder['builder'],
                            'configuration' => $fieldsBuilder['builderConfig'] ?? null,
                        ]));
                    }
                }
                foreach ($config['fields'] as $fieldName => $fieldConfig) {
                    $fieldConfiguration = new FieldConfiguration($fieldName, $fieldConfig['type'] ?? '--replaced-by-builder--');
                    $this->setCommonProperties($fieldConfiguration, $fieldConfig);
                    if (isset($fieldConfig['resolve'])) {
                        $fieldConfiguration->setResolver($fieldConfig['resolve']);
                    }
                    if (isset($fieldConfig['args'])) {
                        foreach ($fieldConfig['args'] as $argName => $argConfig) {
                            $argumentConfiguration = new ArgumentConfiguration($argName, $argConfig['type']);
                            $this->setCommonProperties($argumentConfiguration, $argConfig);
                            $fieldConfiguration->addArgument($argumentConfiguration);
                        }
                    }
                    if (isset($fieldConfig['builder'])) {
                        $fieldConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
                            'type' => BuilderExtension::TYPE_FIELD,
                            'name' => $fieldConfig['builder'],
                            'configuration' => $fieldConfig['configuration'] ?? null,
                        ]));
                    }
                    if (isset($fieldConfig['argsBuilder'])) {
                        if (is_string($fieldConfiguration['argsBuilder'])) {
                            $name = $fieldConfiguration['argsBuilder'];
                            $configuration = null;
                        } else {
                            $name = $fieldConfiguration['argsBuilder']['builder'];
                            $configuration = $fieldConfiguration['argsBuilder']['config'] ?? null;
                        }
                        $fieldConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
                            'type' => BuilderExtension::TYPE_ARGS,
                            'name' => $name,
                            'configuration' => $configuration,
                        ]));
                    }
                    if (isset($fieldConfig['access'])) {
                        $fieldConfiguration->addExtension(new ExtensionConfiguration(AccessExtension::NAME, $fieldConfig['access']));
                    }
                    if (isset($fieldConfig['public'])) {
                        $fieldConfiguration->addExtension(new ExtensionConfiguration(IsPublicExtension::NAME, $fieldConfig['public']));
                    }

                    $typeConfiguration->addField($fieldConfiguration);
                }
                break;
            case TypesConfiguration::TYPE_INPUT:
                $typeConfiguration = new InputConfiguration($name);
                foreach ($config['fields'] as $fieldName => $fieldConfig) {
                    $fieldConfiguration = new InputFieldConfiguration($fieldName, $fieldConfig['type']);
                    $this->setCommonProperties($fieldConfiguration, $fieldConfig);
                    if (isset($fieldConfig['defaultValue'])) {
                        $fieldConfiguration->setDefaultValue($fieldConfig['defaultValue']);
                    }
                    $typeConfiguration->addField($fieldConfiguration);
                }
                break;
            case TypesConfiguration::TYPE_SCALAR:
                $typeConfiguration = new ScalarConfiguration($name);
                if (isset($config['scalarType'])) {
                    $typeConfiguration->setScalarType($config['scalarType']);
                }
                if (isset($config['serialize'])) {
                    $typeConfiguration->setSerialize($config['serialize']);
                }
                if (isset($config['parseValue'])) {
                    $typeConfiguration->setParseValue($config['parseValue']);
                }
                if (isset($config['parseLiteral'])) {
                    $typeConfiguration->setParseLiteral($config['parseLiteral']);
                }
                break;
            case TypesConfiguration::TYPE_ENUM:
                $typeConfiguration = new EnumConfiguration($name);
                foreach ($config['values'] as $name => $valueConfig) {
                    $valueConfiguration = new EnumValueConfiguration($name, $valueConfig['value']);
                    $this->setCommonProperties($valueConfiguration, $valueConfig);
                    $typeConfiguration->addValue($valueConfiguration);
                }
                break;
            case TypesConfiguration::TYPE_UNION:
                $typeConfiguration = new UnionConfiguration($name);
                $typeConfiguration->setTypes($config['types']);
                $typeConfiguration->setTypeResolver($config['resolveType']);
                break;
            default:
                return null;
        }

        $this->setCommonProperties($typeConfiguration, $config);

        return $typeConfiguration;
    }

    protected function setCommonProperties(TypeConfiguration $typeConfiguration, array $config)
    {
        if (isset($config['description'])) {
            $typeConfiguration->setDescription($config['description']);
        }

        if (isset($config['deprecationReason'])) {
            $typeConfiguration->setDeprecation($config['deprecationReason']);
        }

        foreach ($config['extensions'] as $extension) {
            $typeConfiguration->addExtension(new ExtensionConfiguration($extension['name'], $extension['configuration']));
        }
    }
}
