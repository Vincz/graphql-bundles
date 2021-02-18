<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\ConfigurationYamlParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use const DIRECTORY_SEPARATOR;

class ConfigurationYamlParserTest extends WebTestCase
{
    public function testParseConstants(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'constant';
        $expected = ['value' => Constants::TWILEK];

        $parser = new ConfigurationYamlParser([$dirname]);
        $actual = $parser->getConfiguration();
        $this->assertSame($expected, $actual);
    }

    public function testBrokenYaml():void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'broken';
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('#The file "(.*)'.preg_quote(DIRECTORY_SEPARATOR).'broken.types.yml" does not contain valid YAML\.#');
        $parser = new ConfigurationYamlParser([$dirname]);
        $parser->getConfiguration();
    }
}
