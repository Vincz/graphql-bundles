<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Type;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 * @GQL\FieldsBuilder(name="MyFieldsBuilder", configuration={"param1": "val1"})
 */
#[GQL\Type]
#[GQL\FieldsBuilder(name: "MyFieldsBuilder", configuration: ["param1" => "val1"])]
class Crystal
{
    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: "String!")]
    protected string $color;
}
