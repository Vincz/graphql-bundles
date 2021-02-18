<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Union(resolveType="value.getType()")
 */
#[GQL\Union(resolveType: "value.getType()")]
interface Killable
{
}
