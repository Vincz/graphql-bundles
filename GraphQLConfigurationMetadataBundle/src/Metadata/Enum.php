<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Enum extends Metadata
{
    /**
     * Enum name.
     */
    public ?string $name;

    /**
     * @var array<\Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\EnumValue>
     *
     * @deprecated
     */
    public array $values;

    /**
     * @param string|null      $name   The GraphQL name of the enum
     * @param array<EnumValue> $values An array of @GQL\EnumValue @deprecated
     */
    public function __construct(?string $name = null, array $values = [])
    {
        $this->name = $name;
        $this->values = $values;
        if (!empty($values)) {
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "values" on annotation @GQL\Enum is deprecated as of 0.14 and will be removed in 1.0. Use the @GQL\EnumValue annotation on the class itself instead.', E_USER_DEPRECATED);
        }
    }
}
