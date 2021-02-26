<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Tests;

use Exception;
use Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter\CustomScalarNode;
use Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ConfigurationGraphQLParser;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function sprintf;
use const DIRECTORY_SEPARATOR;

class ConfigurationGraphQLTest extends WebTestCase
{
    protected static function cleanConfig(array $config): array
    {
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                $value = self::cleanConfig($value);
            }
        }

        return array_filter($config, function ($item) {
            return !is_array($item) || !empty($item);
        });
    }

    protected function getConfiguration(string $directory = null)
    {
        $directories = null !== $directory ? [$directory] : [__DIR__.'/fixtures/schema'];
        $generator = new ConfigurationGraphQLParser($directories);

        return $generator->getConfiguration();
    }

    protected function parseFile(string $dirname)
    {
        $parser = new ConfigurationGraphQLParser([$dirname]);

        return $parser->getConfiguration();
    }

    public function testParse(): void
    {
        $configuration = $this->getConfiguration();
        $types = array_map(fn (TypeConfiguration $type) => $type->toArray(), $configuration->getTypes());
        $expected = include __DIR__.'/fixtures/schema.php';

        $this->assertSame($expected, $types);
    }

    public function testParseEmptyFile(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'empty';
        $configuration = $this->getConfiguration($dirname);
        $this->assertCount(0, $configuration->getTypes());
    }

    public function testParseInvalidFile(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'invalid';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('An error occurred while parsing the file "%s"', $dirname.DIRECTORY_SEPARATOR.'invalid.graphql'));
        $this->getConfiguration($dirname);
    }

    public function testParseNotSupportedSchemaDefinition(): void
    {
        $dirname = __DIR__.'/fixtures/unsupported';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Schema definition is not supported right now.');
        $this->getConfiguration($dirname);
    }

    public function testCustomScalarTypeDefaultFieldValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Config entry must be override with ResolverMap to be used.');
        CustomScalarNode::mustOverrideConfig();
    }
}
