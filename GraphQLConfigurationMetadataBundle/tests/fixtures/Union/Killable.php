<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Union(typeResolver="value.getType()")
 */
#[GQL\Union(typeResolver: "value.getType()")]
interface Killable
{
}
