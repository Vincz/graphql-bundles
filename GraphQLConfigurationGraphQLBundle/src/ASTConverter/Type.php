<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;

class Type
{
    public static function get(Node $node): string
    {
        $type = '';
        switch ($node->kind) {
            case NodeKind::NAMED_TYPE:
                $type = $node->name->value;
                break;

            case NodeKind::NON_NULL_TYPE:
                $type = self::get($node->type).'!';
                break;

            case NodeKind::LIST_TYPE:
                $type = '['.self::get($node->type).']';
                break;
        }

        return $type;
    }
}
