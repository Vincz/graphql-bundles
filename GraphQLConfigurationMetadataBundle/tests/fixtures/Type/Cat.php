<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 * @GQL\Description("The Cat type")
 */
#[GQL\Type]
#[GQL\Description("The Cat type")]
class Cat extends Animal
{
    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: "Int!")]
    protected int $lives;

    /**
     * @GQL\Field
     *
     * @var string[]
     */
    #[GQL\Field]
    protected array $toys;
}
