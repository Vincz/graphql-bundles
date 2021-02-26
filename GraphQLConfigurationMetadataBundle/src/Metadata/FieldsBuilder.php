<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;

/**
 * Annotation for GraphQL fields builders.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"ANNOTATION", "CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class FieldsBuilder extends Builder
{
    public function getBuilderType(): string
    {
        return BuilderExtension::TYPE_FIELDS;
    }
}
