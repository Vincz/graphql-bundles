<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

/**
 * @GQL\Union(types={"Hero", "Droid", "Sith"})
 */
#[GQL\Union(types: ["Hero", "Droid", "Sith"])]
class SearchResult2
{
    public static function resolveType(TypeResolver $typeResolver, bool $value): ?Type
    {
        return $typeResolver->resolve('Hero');
    }
}
