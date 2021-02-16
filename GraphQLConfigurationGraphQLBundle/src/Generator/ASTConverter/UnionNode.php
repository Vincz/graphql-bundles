<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Generator\ASTConverter;

use GraphQL\Language\AST\Node;

class UnionNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        $config = DescriptionNode::toConfig($node);

        if (!empty($node->types)) {
            $types = [];
            foreach ($node->types as $type) {
                $types[] = TypeNode::astTypeNodeToString($type);
            }
            $config['types'] = $types;
        }

        return [
            'type' => 'union',
            'config' => $config,
        ];
    }
}