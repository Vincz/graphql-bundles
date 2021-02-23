<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Deprecated;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 * @GQL\FieldsBuilder(name="MyFieldsBuilder", configuration={"param1": "val1"})
 */
class DeprecatedBuilderAttributes
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $color;
}
