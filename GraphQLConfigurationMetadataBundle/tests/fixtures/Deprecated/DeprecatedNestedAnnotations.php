<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Deprecated;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type(builders={@GQL\FieldsBuilder(name="MyFieldsBuilder", configuration={"param1": "val1"})})
 */
class DeprecatedNestedAnnotations
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $color;

    /**
     * @GQL\Field(args={
     *   @GQL\Arg(name="arg1", type="String!"),
     *   @GQL\Arg(name="arg2", type="Int!")
     * })
     */
    public function getList(string $arg1, int $arg2): bool
    {
        return true;
    }
}
