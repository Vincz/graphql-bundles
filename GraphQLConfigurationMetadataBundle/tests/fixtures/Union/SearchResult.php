<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Union(name="ResultSearch", types={"Hero", "Droid", "Sith"}, typeResolver="value.getType()")
 * @GQL\Description("A search result")
 */
#[GQL\Union("ResultSearch", types: ["Hero", "Droid", "Sith"], typeResolver: "value.getType()")]
#[GQL\Description("A search result")]
class SearchResult
{
}
