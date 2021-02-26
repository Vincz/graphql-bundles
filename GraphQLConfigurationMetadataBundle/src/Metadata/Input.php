<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL input type.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Input extends Metadata
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
