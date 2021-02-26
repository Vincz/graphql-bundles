<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\Directive;

class Deprecated
{
    public static function get(Node $node): ?string
    {
        foreach ($node->directives as $directiveDef) {
            if ('deprecated' === $directiveDef->name->value) {
                $reason = $directiveDef->arguments->count() ?
                    $directiveDef->arguments[0]->value->value : Directive::DEFAULT_DEPRECATION_REASON;

                return $reason;
            }
        }

        return null;
    }
}
