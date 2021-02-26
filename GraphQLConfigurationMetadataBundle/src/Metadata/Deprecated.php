<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL to mark a field as deprecated.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS_CONSTANT)]
final class Deprecated extends Metadata
{
    /**
     * The deprecation reason.
     */
    public string $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }
}
