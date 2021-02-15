<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL input type.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Input extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Type name.
     */
    public ?string $name;

    /**
     * Is the type a relay input.
     */
    public bool $isRelay = false;

    public function __construct(string $name = null, bool $isRelay = false)
    {
        $this->name = $name;
        $this->isRelay = $isRelay;
    }
}
