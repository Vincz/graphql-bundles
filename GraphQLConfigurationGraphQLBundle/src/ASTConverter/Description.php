<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use function trim;

class Description
{
    public static function get(Node $node): ?string
    {
        return self::cleanAstDescription($node->description);
    }

    private static function cleanAstDescription(?StringValueNode $description): ?string
    {
        if (null === $description) {
            return null;
        }

        $description = trim($description->value);

        return empty($description) ? null : $description;
    }
}
