<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\ConfigurationXmlParser;

class ConfigurationXmlParserTest extends ConfigurationParserTest
{
    const PARSER_CLASS = ConfigurationXmlParser::class;
    protected array $excludeDirectories = ['broken', 'constant'];
}
