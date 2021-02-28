<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\ConfigurationYamlParser;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;
use const DIRECTORY_SEPARATOR;

abstract class ConfigurationParserTest extends WebTestCase
{
    protected Configuration $configuration;
    protected array $excludeDirectories = [];

    public function setUp(): void
    {
        parent::setup();
        $this->configuration = unserialize(serialize($this->getConfiguration()));
    }

    protected function getConfiguration(array $includeDirectories = [])
    {
        $finder = Finder::create()
            ->in(__DIR__.'/fixtures')
            ->directories();
        foreach ($this->excludeDirectories as $exclude) {
            $finder = $finder->exclude($exclude);
        }
        $directories = array_values(array_map(fn (SplFileInfo $dir) => $dir->getPathname(), iterator_to_array($finder->getIterator())));
        $directories = [...$directories, ...$includeDirectories];

        $parser = static::PARSER_CLASS;
        $generator = new $parser($directories);

        return $generator->getConfiguration();
    }

    protected function getType(string $name, string $configurationClass = null)
    {
        $type = $this->configuration->getType($name);
        if (!$type) {
            $this->fail(sprintf('Unable to retrieve type "%s" from configuration', $name));
        }
        $this->assertNotNull($type);
        if ($configurationClass) {
            $this->assertInstanceOf($configurationClass, $type);
        }

        return $type;
    }

    public function testQuery(): void
    {
        $object = $this->getType('Query', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Query',
            'fields' => [
                ['name' => 'node'],
                [
                    'name' => 'allObjects',
                    'type' => '[NodeInterface]',
                    'resolver' => '@=service("overblog_graphql.test.resolver.global").resolveAllObjects()',
                ],
            ],
        ], $object->toArray());
    }

    /*
            node:
                builder: 'Relay::Node'
                builderConfig:
                    nodeInterfaceType: NodeInterface
                    idFetcher: '@=service("overblog_graphql.test.resolver.global").idFetcher(value)'
            allObjects:
                type: '[NodeInterface]'
                resolve: '@=service("overblog_graphql.test.resolver.global").resolveAllObjects()'

    */

    /*
    public function testParseConstants(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'constant';
        $expected = ['value' => Constants::TWILEK];

        $parser = new ConfigurationYamlParser([$dirname]);
        $actual = $parser->getConfiguration();
        $this->assertSame($expected, $actual);
    }

    public function testBrokenYaml(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'broken';
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('#The file "(.*)'.preg_quote(DIRECTORY_SEPARATOR).'broken.types.yml" does not contain valid YAML\.#');
        $parser = new ConfigurationYamlParser([$dirname]);
        $parser->getConfiguration();
    }
    */
}
