<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;

class EnumNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        $config = DescriptionNode::toConfig($node);

        $values = [];

        foreach ($node->values as $value) {
            $values[$value->name->value] = DescriptionNode::toConfig($value) + [
                'value' => $value->name->value,
            ];

            $directiveConfig = DirectiveNode::toConfig($value);

            if (isset($directiveConfig['deprecationReason'])) {
                $values[$value->name->value]['deprecationReason'] = $directiveConfig['deprecationReason'];
            }
        }
        $config['values'] = $values;

        return [
            'type' => 'enum',
            'config' => $config,
        ];
    }
}