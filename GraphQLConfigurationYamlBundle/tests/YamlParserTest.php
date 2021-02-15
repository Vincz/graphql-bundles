<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Tests;

use Overblog\GraphQLBundle\Config\Parser\YamlParser;
use SplFileInfo;
use const DIRECTORY_SEPARATOR;

class YamlParserTest extends TestCase
{
    public function testParseConstants(): void
    {
        $fileName = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'constants.yml';
        $expected = ['value' => Constants::TWILEK];

        $actual = YamlParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
        $this->assertSame($expected, $actual);
    }
}
