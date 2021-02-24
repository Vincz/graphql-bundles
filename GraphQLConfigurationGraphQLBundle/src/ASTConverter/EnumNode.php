<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\EnumConfiguration;
use Overblog\GraphQLBundle\Configuration\EnumValueConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;

class EnumNode implements NodeInterface
{
    public static function toConfiguration(Node $node): TypeConfiguration
    {
        $enumConfiguration = new EnumConfiguration('');
        $enumConfiguration->setDeprecation(Deprecated::get($node));
        $enumConfiguration->setDescription(Description::get($node));
        $enumConfiguration->addExtensions(Extensions::get($node));

        foreach ($node->values as $value) {
            $valueConfiguration = new EnumValueConfiguration($value->name->value, $value->name->value);
            $valueConfiguration->setDeprecation(Deprecated::get($value));
            $valueConfiguration->setDescription(Description::get($value));
            $valueConfiguration->addExtensions(Extensions::get($value));
            
            $enumConfiguration->addValue($valueConfiguration);
        }

        return $enumConfiguration;
    }
}
