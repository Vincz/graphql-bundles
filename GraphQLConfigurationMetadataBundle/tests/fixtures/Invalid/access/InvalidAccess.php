<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\access;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
class InvalidAccess
{
    /**
     * @GQL\Access("access")
     */
    #[GQL\Access("access")]
    protected string $field;
}
