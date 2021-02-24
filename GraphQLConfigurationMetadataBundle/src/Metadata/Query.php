<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL query.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Query extends Field
{
    /**
     * The target types to attach this query to.
     *
     * @var array<string>
     */
    public ?array $targetTypes;

    /**
     * {@inheritdoc}
     *
     * @param string|string[]|null $targetTypes
     * @param string|string[]|null $targetType
     */
    public function __construct(
        string $name = null,
        string $type = null,
        array $args = [],
        string $resolve = null,
        $argsBuilder = null,
        $fieldBuilder = null,
        string $complexity = null,
        $targetTypes = null,
        $targetType = null
    ) {
        parent::__construct($name, $type, $args, $resolve, $argsBuilder, $fieldBuilder, $complexity);
        if ($targetTypes) {
            $this->targetTypes = is_string($targetTypes) ? [$targetTypes] : $targetTypes;
        } elseif ($targetType) {
            $this->targetTypes = is_string($targetType) ? [$targetType] : $targetType;
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "targetType" on annotation @GQL\Query is deprecated as of 0.14 and will be removed in 1.0. Use the "targetTypes" attributes instead.', E_USER_DEPRECATED);
        }
    }
}
