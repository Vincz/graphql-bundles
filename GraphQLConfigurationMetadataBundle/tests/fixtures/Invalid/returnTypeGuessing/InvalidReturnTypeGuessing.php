<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\returnTypeGuessing;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
class InvalidReturnTypeGuessing
{
    /**
     * @GQL\Field(name="guessFailed")
     * @phpstan-ignore-next-line
     */
    #[GQL\Field(name: "guessFailed")]
    // @phpstan-ignore-next-line
    public function guessFail(int $test)
    {
        return 12;
    }
}
