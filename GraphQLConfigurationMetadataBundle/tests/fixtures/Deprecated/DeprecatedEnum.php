<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Deprecated;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Enum(values={
 *      @GQL\EnumValue(name="P1", description="P1 description"),
 *      @GQL\EnumValue(name="P2", deprecationReason="P2 deprecated"),
 * })
 */
class DeprecatedEnum
{
    const P1 = 1;
    const P2 = 2;
}
