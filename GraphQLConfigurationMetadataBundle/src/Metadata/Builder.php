<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;

/**
 * Annotation for GraphQL builders
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
abstract class Builder extends Extension
{
    /**
     * @param string|null $name          The name of the builder
     * @param array       $configuration The builder configuration array
     */
    public function __construct(string $name = null, array $configuration = [])
    {
        $this->name = BuilderExtension::ALIAS;
        $this->configuration = [
            'name' => $name,
            'configuration' => $configuration,
        ];
    }
}
