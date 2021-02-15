<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as GQL;

/**
 * @GQL\Union(name="ResultSearch", types={"Hero", "Droid", "Sith"}, resolveType="value.getType()")
 * @GQL\Description("A search result")
 */
#[GQL\Union("ResultSearch", types: ["Hero", "Droid", "Sith"], resolveType: "value.getType()")]
#[GQL\Description("A search result")]
class SearchResult
{
}
