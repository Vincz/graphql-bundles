<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\union;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Union(types={"Hero", "Droid", "Sith"})
 */
#[GQL\Union(types: ["Hero", "Droid", "Sith"])]
class InvalidUnion
{
}
