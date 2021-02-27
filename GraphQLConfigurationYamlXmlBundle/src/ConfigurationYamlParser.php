<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle;

use SplFileInfo;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use function sprintf;

class ConfigurationYamlParser extends ConfigurationParser
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

    protected function parseFile(SplFileInfo $file): array
    {
        try {
            $typesConfig = $this->getYamlParser()->parse(file_get_contents($file->getPathname()), Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new InvalidConfigurationException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return is_array($typesConfig) ? $typesConfig : [];
    }
}
