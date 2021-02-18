<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL public on fields.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class IsPublic extends Metadata implements NamedArgumentConstructorAnnotation
{
    /**
     * Field publicity.
     */
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
