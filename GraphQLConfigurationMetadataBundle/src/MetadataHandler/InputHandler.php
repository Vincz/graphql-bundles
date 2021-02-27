<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\InputConfiguration;
use Overblog\GraphQLBundle\Configuration\InputFieldConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use ReflectionClass;
use ReflectionProperty;

class InputHandler extends MetadataHandler
{
    const TYPE = TypeConfiguration::TYPE_INPUT;

    protected function getInputName(ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): string
    {
        return $inputMetadata->name ?? $this->suffixName($reflectionClass->getShortName(), 'Input');
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): void
    {
        $gqlName = $this->getInputName($reflectionClass, $inputMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Create a GraphQL Input type configuration from metadatas on properties.
     *
     * @return array{type: 'relay-mutation-input'|'input-object', config: array}
     */
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): ?TypeConfiguration
    {
        $gqlName = $this->getInputName($reflectionClass, $inputMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $inputConfiguration = InputConfiguration::get($gqlName)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->addExtensions($this->getExtensions($metadatas))
            ->setOrigin($this->getOrigin($reflectionClass));

        $fields = $this->getGraphQLInputFieldsFromMetadatas($reflectionClass, $this->getClassProperties($reflectionClass));

        foreach ($fields as $field) {
            $inputConfiguration->addField($field);
        }

        $configuration->addType($inputConfiguration);

        return $inputConfiguration;
    }

    /**
     * Create GraphQL input fields configuration based on metadatas.
     *
     * @param ReflectionProperty[] $reflectors
     *
     * @throws AnnotationException
     *
     * @return InputFieldConfiguration[]
     */
    protected function getGraphQLInputFieldsFromMetadatas(ReflectionClass $reflectionClass, array $reflectors): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $metadatas = $this->getMetadatas($reflector);

            /** @var Metadata\Field|null $fieldMetadata */
            $fieldMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\InputField::class);

            // No field metadata found
            if (null === $fieldMetadata) {
                $fieldMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\Field::class);
                if (null === $fieldMetadata) {
                    continue;
                }
                trigger_deprecation('overblog/graphql', '0.14', "The use of @GQL\Field or #GQL\Field on Input field is deprecated. Use @GQL\InputField or #GQL\InputField instead");
                // Ignore field with resolver when the type is an Input
                if (isset($fieldMetadata->resolve)) {
                    continue;
                }
            }

            if (isset($fieldMetadata->type)) {
                $fieldType = $fieldMetadata->type;
            } else {
                try {
                    $fieldType = $this->typeGuesser->guessType($reflectionClass, $reflector, TypeConfiguration::VALID_INPUT_TYPES);
                } catch (TypeGuessingException $e) {
                    throw new MetadataConfigurationException(sprintf('The attribute "type" on %s is missing on property "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", $this->formatMetadata(Metadata\Field::class), $reflector->getName(), $e->getMessage()));
                }
            }

            $fieldConfiguration = InputFieldConfiguration::get($reflector->getName(), $fieldType)
                ->setDescription($this->getDescription($metadatas))
                ->setDeprecation($this->getDeprecation($metadatas))
                ->addExtensions($this->getExtensions($metadatas))
                ->setOrigin($this->getOrigin($reflector));

            if ($fieldMetadata instanceof Metadata\InputField) {
                if (null !== $fieldMetadata->defaultValue) {
                    $fieldConfiguration->setDefaultValue($fieldMetadata->defaultValue);
                } elseif (PHP_VERSION_ID >= 80000 && $reflector->hasDefaultValue()) {
                    $fieldConfiguration->setDefaultValue($reflector->getDefaultValue());
                }
            }

            $fields[] = $fieldConfiguration;
        }

        return $fields;
    }
}
