<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Utils\AST;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;

class Extensions
{
    public static function get(Node $node): array
    {
        $extensions = [];
        foreach ($node->directives as $directiveDef) {
            if ('ext' === $directiveDef->name->value) {
                $name = $directiveDef->arguments[0]->value->value;
                $configuration = AST::valueFromASTUntyped($directiveDef->arguments[1]->value);
                $extensions[] = new ExtensionConfiguration($name, $configuration);
            }
        }

        return $extensions;
    }
}
