<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\provider2;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Provider
 */
#[GQL\Provider]
class InvalidProvider
{
    /**
     * @GQL\Mutation(type="Int", targetType="RootQuery2")
     */
    #[GQL\Mutation(type: "Int", targetType: "RootQuery2")]
    public function noMutationOnQuery(): array
    {
        return [];
    }
}
