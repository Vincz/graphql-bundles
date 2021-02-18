<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Relay;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

/**
 * @GQL\Relay\Edge(node="Character")
 */
#[GQL\Relay\Edge(node: "Character")]
class FriendsConnectionEdge extends Edge
{
}
