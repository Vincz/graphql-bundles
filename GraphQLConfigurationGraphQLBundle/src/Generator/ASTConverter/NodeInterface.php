<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\Generator\ASTConverter;

use GraphQL\Language\AST\Node;

interface NodeInterface
{
    public static function toConfig(Node $node): array;
}
