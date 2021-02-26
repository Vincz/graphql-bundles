<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\Relay;

use Attribute;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\Type;

/**
 * Annotation for GraphQL relay connection.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Connection extends Type
{
    /**
     * Connection Edge type.
     */
    public ?string $edge;

    /**
     * Connection Node type.
     */
    public ?string $node;

    public function __construct(string $edge = null, string $node = null)
    {
        $this->edge = $edge;
        $this->node = $node;
    }
}
