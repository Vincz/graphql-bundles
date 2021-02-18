<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union\Killable;

/**
 * @GQL\Type
 */
#[GQL\Type]
class Mandalorian extends Character implements Killable, Armored
{
}
