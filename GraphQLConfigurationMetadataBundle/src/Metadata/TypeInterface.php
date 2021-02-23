<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL interface.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TypeInterface extends Metadata
{
    /**
     * Interface name.
     */
    public ?string $name;

    /**
     * Resolver type for interface.
     */
    public string $typeResolver;

    /**
     * @param string|null $name         The GraphQL name of the interface
     * @param string      $typeResolver The express resolve type
     */
    public function __construct(string $name = null, string $typeResolver)
    {
        $this->name = $name;
        $this->typeResolver = $typeResolver;
    }
}
