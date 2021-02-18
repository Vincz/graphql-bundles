<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type(isTypeOf="@=isTypeOf('App\Entity\Droid')")
 * @GQL\Description("The Droid type")
 */
#[GQL\Type(isTypeOf: "@=isTypeOf('App\Entity\Droid')")]
#[GQL\Description("The Droid type")]
class Droid extends Character
{
    /**
     * @GQL\Field
     */
    #[GQL\Field]
    protected int $memory;
}
