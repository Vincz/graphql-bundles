<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Relay;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;

/**
 * @GQL\Relay\Connection(edge="FriendsConnectionEdge")
 */
#[GQL\Relay\Connection(edge: "FriendsConnectionEdge")]
class FriendsConnection extends Connection
{
}
