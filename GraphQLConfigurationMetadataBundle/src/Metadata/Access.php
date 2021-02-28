<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;
use Overblog\GraphQLBundle\Extension\Access\AccessExtension;

/**
 * Annotation for GraphQL access on fields.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Access extends Extension
{
    public function __construct(string $expression)
    {
        $this->name = AccessExtension::ALIAS;
        $this->configuration = $expression;
    }
}
