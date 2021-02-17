<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\ConfigurationYamlParser;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const DIRECTORY_SEPARATOR;

class ConfigurationYamlTest extends WebTestCase
{
    public function testParseConstants(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures';
        $expected = ['value' => Constants::TWILEK];

        $parser = new ConfigurationYamlParser([$dirname]);
        $actual = $parser->getConfiguration();
        $this->assertSame($expected, $actual);
    }
}
