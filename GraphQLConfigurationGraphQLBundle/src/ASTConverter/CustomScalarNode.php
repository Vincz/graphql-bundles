<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\ScalarConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use RuntimeException;

class CustomScalarNode implements NodeInterface
{
    public static function toConfiguration(string $name, Node $node): TypeConfiguration
    {
        $scalarConfiguration = ScalarConfiguration::get($name)
            ->setDeprecation(Deprecated::get($node))
            ->setDescription(Description::get($node))
            ->addExtensions(Extensions::get($node));

        $mustOverride = sprintf('%s::%s', __CLASS__, 'mustOverrideConfig');

        $scalarConfiguration->setSerialize($mustOverride);
        $scalarConfiguration->setParseValue($mustOverride);
        $scalarConfiguration->setParseLiteral($mustOverride);

        return $scalarConfiguration;
    }

    public static function mustOverrideConfig(): void
    {
        throw new RuntimeException('Config entry must be override with ResolverMap to be used.');
    }
}
