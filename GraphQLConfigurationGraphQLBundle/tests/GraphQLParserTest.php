<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Tests;

use Exception;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\CustomScalarNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQLParser;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function sprintf;
use const DIRECTORY_SEPARATOR;

class GraphQLParserTest extends TestCase
{
    public function testParse(): void
    {
        $fileName = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'schema.graphql';
        $expected = include __DIR__.'/fixtures/schema.php';

        $this->assertContainerAddFileToResources($fileName);
        $config = GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
        $this->assertSame($expected, self::cleanConfig($config));
    }

    public function testParseEmptyFile(): void
    {
        $fileName = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'empty.graphql';

        $this->assertContainerAddFileToResources($fileName);

        $config = GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
        $this->assertSame([], $config);
    }

    public function testParseInvalidFile(): void
    {
        $fileName = __DIR__.'/fixtures/invalid.graphql';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('An error occurred while parsing the file "%s"', $fileName));
        GraphQLParser::parse(new SplFileInfo($fileName), $this->containerBuilder);
    }

    public function testParseNotSupportedSchemaDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Schema definition is not supported right now.');
        GraphQLParser::parse(new SplFileInfo(__DIR__.'/fixtures/not-supported-schema-definition.graphql'), $this->containerBuilder);
    }

    public function testCustomScalarTypeDefaultFieldValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Config entry must be override with ResolverMap to be used.');
        CustomScalarNode::mustOverrideConfig();
    }
}
