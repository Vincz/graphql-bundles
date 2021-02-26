<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\Relay;

use Attribute;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\Type;

/**
 * Annotation for GraphQL connection edge.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Edge extends Type
{
    /**
     * Edge Node type.
     */
    public string $node;

    public function __construct(string $node)
    {
        $this->node = $node;
    }
}
