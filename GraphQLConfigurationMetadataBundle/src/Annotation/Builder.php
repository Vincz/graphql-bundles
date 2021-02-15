<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL builders
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
abstract class Builder extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Builder name.
     */
    public string $name;

    /**
     * The builder config.
     */
    public array $config = [];

    /**
     * @param string|null $name   The name of the builder
     * @param array       $config The builder configuration array
     */
    public function __construct(string $name = null, array $config = [])
    {
        $this->name = $name;
        $this->config = $config;
    }
}
