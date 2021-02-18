<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Relay;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;

/**
 * @GQL\Relay\Connection(node="Character")
 */
#[GQL\Relay\Connection(node: "Character")]
class EnemiesConnection extends Connection
{
}
