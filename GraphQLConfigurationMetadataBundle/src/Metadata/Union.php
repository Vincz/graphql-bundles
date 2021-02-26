<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Union extends Metadata
{
    /**
     * Union name.
     */
    public ?string $name;

    /**
     * Union types.
     */
    public array $types = [];

    /**
     * Resolver type for union.
     */
    public ?string $typeResolver;

    /**
     * @param string|null $name         The GraphQL name of the union
     * @param string[]    $types        List of types included in the union
     * @param string|null $typeResolver The resolve type expression
     */
    public function __construct(string $name = null, array $types = [], string $typeResolver = null)
    {
        $this->name = $name;
        $this->types = $types;
        $this->typeResolver = $typeResolver;
    }
}
