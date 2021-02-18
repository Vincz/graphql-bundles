<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 * @GQL\Description("The character interface")
 */
#[GQL\Type]
#[GQL\Description("The character interface")]
abstract class Animal
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Description("The name of the animal")
     */
    #[GQL\Field(type: "String!")]
    #[GQL\Description("The name of the animal")]
    private string $name;

    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: "String!")]
    private string $lives;
}
