<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL scalar.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Scalar extends Metadata
{
    public ?string $name;

    public ?string $scalarType;

    /**
     * @param string|null $name       The GraphQL name of the Scalar
     * @param string|null $scalarType Expression to reuse an other scalar type
     */
    public function __construct(string $name = null, string $scalarType = null)
    {
        $this->name = $name;
        $this->scalarType = $scalarType;
    }
}
