<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Relay;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Type;

/**
 * Annotation for GraphQL relay connection.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Connection extends Type implements NamedArgumentConstructorAnnotation
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