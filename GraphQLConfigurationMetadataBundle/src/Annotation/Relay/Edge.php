<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Relay;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Type;

/**
 * Annotation for GraphQL connection edge.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Edge extends Type implements NamedArgumentConstructorAnnotation
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
