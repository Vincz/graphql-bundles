<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Tests;

use Exception;
use Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Generator\ConfigurationGraphQLParser;
use Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Generator\ASTConverter\CustomScalarNode;
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

    protected function parseFile(string $dirname)
    {
        $generator = new ConfigurationGraphQLParser([$dirname]);

        return $generator->getConfiguration();
    }

    public function testParse(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema';
        $expected = include __DIR__.'/fixtures/schema.php';
        $config = $this->parseFile($dirname);
        //$config = GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
        $this->assertSame($expected, self::cleanConfig($config));
    }

    public function testParseEmptyFile(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'empty';
        $config = $this->parseFile($dirname);

        //$config = GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
        $this->assertSame([], $config);
    }

    public function testParseInvalidFile(): void
    {
        $dirname = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'invalid';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('An error occurred while parsing the file "%s"', $dirname.DIRECTORY_SEPARATOR.'invalid.graphql'));
        $this->parseFile($dirname);
        //GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
    }

    public function testParseNotSupportedSchemaDefinition(): void
    {
        $dirname = __DIR__.'/fixtures/unsupported';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Schema definition is not supported right now.');
        $this->parseFile($dirname);
        //GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
    }

    public function testCustomScalarTypeDefaultFieldValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Config entry must be override with ResolverMap to be used.');
        CustomScalarNode::mustOverrideConfig();
    }
}
