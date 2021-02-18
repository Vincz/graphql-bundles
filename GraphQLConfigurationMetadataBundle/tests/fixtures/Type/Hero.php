<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Enum\Race;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Union\Killable;

/**
 * @GQL\Type(interfaces={"Character"})
 * @GQL\Description("The Hero type")
 */
#[GQL\Type(interfaces: ["Character"])]
#[GQL\Description("The Hero type")]
class Hero extends Character implements Killable
{
    /**
     * @GQL\Field(type="Race")
     */
    #[GQL\Field(type: "Race")]
    protected Race $race;
}
