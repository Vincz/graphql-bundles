<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension;

abstract class PhpTypeGuesserExtension extends TypeGuesserExtension
{
    /**
     * Convert a PHP Builtin type to a GraphQL type.
     */
    protected function resolveTypeFromPhpType(string $phpType): ?string
    {
        switch ($phpType) {
            case 'boolean':
            case 'bool':
                return 'Boolean';
            case 'integer':
            case 'int':
                return 'Int';
            case 'float':
            case 'double':
                return 'Float';
            case 'string':
                return 'String';
            default:
                return null;
        }
    }
}
