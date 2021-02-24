<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL field.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Field extends Metadata
{
    /**
     * The field name.
     */
    public ?string $name;

    /**
     * Field Type.
     */
    public ?string $type;

    /**
     * Field arguments.
     *
     * @var array<\Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\Arg>
     *
     * @deprecated
     */
    public array $args = [];

    /**
     * Resolver for this property.
     */
    public ?string $resolve;

    /**
     * Args builder.
     *
     * @var mixed
     *
     * @deprecated
     */
    public $argsBuilder;

    /**
     * Field builder.
     *
     * @var mixed
     *
     * @deprecated
     */
    public $fieldBuilder;

    /**
     * Complexity expression.
     *
     * @var string
     *
     * @deprecated
     */
    public ?string $complexity;

    /**
     * @param string|null $name         The GraphQL name of the field
     * @param string|null $type         The GraphQL type of the field
     * @param array       $args         An array of @GQL\Arg to describe arguments @deprecated
     * @param string|null $resolve      A expression resolver to resolve the field value
     * @param mixed|null  $argsBuilder  A @GQL\ArgsBuilder to generate arguments @deprecated
     * @param mixed|null  $fieldBuilder A @GQL\FieldBuilder to generate the field @deprecated
     * @param string|null $complexity   A complexity expression
     */
    public function __construct(
        string $name = null,
        string $type = null,
        array $args = [],
        string $resolve = null,
        $argsBuilder = null,
        $fieldBuilder = null,
        string $complexity = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->resolve = $resolve;
        $this->args = $args;
        $this->argsBuilder = $argsBuilder;
        $this->fieldBuilder = $fieldBuilder;
        $this->complexity = $complexity;

        if (null !== $argsBuilder) {
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "argsBuilder" on annotation @GQL\Field is deprecated as of 0.14 and will be removed in 1.0. Use a @ArgsBuilder annotation on the property or method instead.');
        }

        if (null !== $fieldBuilder) {
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "fieldBuilder" on annotation @GQL\Field is deprecated as of 0.14 and will be removed in 1.0. Use a @FieldBuilder annotation on the property or method instead.');
        }

        if (!empty($args)) {
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "args" on annotation @GQL\Field is deprecated as of 0.14 and will be removed in 1.0. Use the @Arg annotation on the property or method instead.');
        }

        if (null !== $complexity) {
            trigger_deprecation('overblog/graphql-bundle', '0.14', 'The attributes "complexity" on annotation @GQL\Field is deprecated as of 0.14 and will be removed in 1.0. Use the Complexity Extension instead.');
        }
    }
}
