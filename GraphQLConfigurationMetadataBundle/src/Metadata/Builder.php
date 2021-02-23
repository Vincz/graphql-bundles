<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;
use Overblog\GraphQLBundle\Extension\BuilderExtension;

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
        $this->name = BuilderExtension::NAME;
        $this->configuration = [
            'type' => $this->getBuilderType(),
            'name' => $name,
            'configuration' => $configuration
        ];
    }

    abstract public function getBuilderType():string;
}


/**
    public function __construct(string $name = null, array $configuration = null, string $builder = null, array $builderConfig = null)
    {
        parent::__construct($name ?: $builder, $configuration ?: $builderConfig ?: []);
        if (null !== $builder) {
            @trigger_error('The attributes "builder" on annotation @GQL\FieldsBuilder is deprecated as of 0.14 and will be removed in 1.0. Use "name" attribute instead.', E_USER_DEPRECATED);
        }
        if (null !== $builderConfig) {
            @trigger_error('The attributes "builderConfig" on annotation @GQL\FieldsBuilder is deprecated as of 0.14 and will be removed in 1.0. Use "configuration" attribute instead.', E_USER_DEPRECATED);
        }
    }
 **/
