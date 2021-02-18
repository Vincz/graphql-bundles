<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\provider;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Provider
 */
#[GQL\Provider]
class InvalidProvider
{
    /**
     * @GQL\Query(type="Int", targetType="RootMutation2")
     */
    #[GQL\Query(type: "Int", targetType: "RootMutation2")]
    public function noQueryOnMutation(): array
    {
        return [];
    }
}
