<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as GQL;

/**
 * @GQL\TypeInterface(name="WithArmor", resolveType="@=resolver('character_type', [value])")
 * @GQL\Description("The armored interface")
 */
#[GQL\TypeInterface("WithArmor", resolveType: "@=resolver('character_type', [value])")]
#[GQL\Description("The armored interface")]
interface Armored
{
}