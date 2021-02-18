<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationXmlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationXmlBundle\ConfigurationXmlParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use const DIRECTORY_SEPARATOR;

class ConfigurationXmlParserTest extends WebTestCase
{
    public function testBrokenXml():void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'broken';
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('#The file "(.*)'.preg_quote(DIRECTORY_SEPARATOR).'broken.types.xml" does not contain valid XML\.#');
        $parser = new ConfigurationXmlParser([$dirname]);
        $parser->getConfiguration();
    }
}
