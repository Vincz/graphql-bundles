<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\ConfigurationYamlParser;

class ConfigurationYamlParserTest extends ConfigurationParserTest
{
    const PARSER_CLASS = ConfigurationYamlParser::class;
    protected array $excludeDirectories = ['broken', 'constant'];
}
