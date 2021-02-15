<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Generator;

use Overblog\GraphQLBundle\Config\Generator\ConfigurationFilesParser;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use function sprintf;

class ConfigurationYamlParser extends ConfigurationFilesParser
{
    protected Parser $yamlParser;

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

    public function getDirectories(): array
    {
        $directories = [];
        if ($this->rootDirectory) {
            $directories[] = sprintf('%s/config/graphql', $this->rootDirectory);
        }

        foreach ($this->bundlesDirectories as $bundleDirectory) {
            $directories[] = sprintf('%s/Resources/config/graphql', $bundleDirectory);
        }

        return array_unique([...$directories, ...$this->directories]);
    }

    protected function parseFile(SplFileInfo $file): array
    {
        try {
            $typesConfig = $this->getYamlParser()->parse(file_get_contents($file->getPathname()), Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return is_array($typesConfig) ? $typesConfig : [];
    }
}
