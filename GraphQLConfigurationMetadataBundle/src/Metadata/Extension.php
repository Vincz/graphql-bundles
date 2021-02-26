<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL extension.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Extension extends Metadata
{
    /**
     * Extension name.
     */
    public string $name;

    /**
     * @var mixed
     */
    public $configuration;

    /**
     * @param string $name          The name of the extension
     * @param mixed  $configuration The extension configuration
     */
    public function __construct(string $name, $configuration)
    {
        $this->name = $name;
        $this->configuration = $configuration;
    }
}
