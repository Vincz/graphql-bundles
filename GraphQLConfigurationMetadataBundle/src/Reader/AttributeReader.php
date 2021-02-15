<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

class AttributeReader implements MetadataReaderInterface
{
    const METADATA_FORMAT = '#[%s]';

    public function formatMetadata(string $metadataType): string
    {
        return sprintf(self::METADATA_FORMAT, $metadataType);
    }

    public function getMetadatas(Reflector $reflector): array
    {
        $attributes = [];

        switch (true) {
            case $reflector instanceof ReflectionClass:
            case $reflector instanceof ReflectionMethod:
            case $reflector instanceof ReflectionProperty:
            case $reflector instanceof ReflectionClassConstant:
                $attributes = $reflector->getAttributes();
        }

        // @phpstan-ignore-next-line
        return array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes);
    }
}
