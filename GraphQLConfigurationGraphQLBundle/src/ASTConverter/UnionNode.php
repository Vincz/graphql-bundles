<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;
use Overblog\GraphQLBundle\Configuration\UnionConfiguration;

class UnionNode implements NodeInterface
{
    public static function toConfiguration(Node $node): TypeConfigurationInterface
    {
        $unionConfiguration = new UnionConfiguration('');
        $unionConfiguration->setDeprecation(Deprecated::get($node));
        $unionConfiguration->setDescription(Description::get($node));
        $unionConfiguration->addExtensions(Extensions::get($node));


        if (!empty($node->types)) {
            $types = [];
            foreach ($node->types as $type) {
                $types[] = Type::get($type);
            }
            $unionConfiguration->setTypes($types);
        }

        return $unionConfiguration;
    }
}
