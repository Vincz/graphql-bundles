<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\argumentGuessing;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
class InvalidArgumentGuessing
{
    /**
     * @GQL\Field(name="guessFailed")
     *
     * @param mixed $test
     */
    #[GQL\Field(name: "guessFailed")]
    public function guessFail($test): int
    {
        return 12;
    }
}
