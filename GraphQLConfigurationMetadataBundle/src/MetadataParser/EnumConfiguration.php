<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataParser;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use ReflectionClass;
use ReflectionClassConstant;

class EnumConfiguration extends MetadataConfiguration
{
    const TYPE = self::GQL_ENUM;

    protected function getEnumName(ReflectionClass $reflectionClass, Metadata\Metadata $enumMetadata): string
    {
        return $enumMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $enumMetadata): void
    {
        $gqlName = $this->getEnumName($reflectionClass, $enumMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Get a GraphQL Union configuration from given union metadata.
     *
     * @return array{type: 'union', config: array}
     */
    public function getConfiguration(ReflectionClass $reflectionClass, Metadata\Metadata $enumMetadata): array
    {
        $gqlName = $this->getEnumName($reflectionClass, $enumMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);
        $enumValues = array_merge($this->getMetadataMatching($metadatas, Metadata\EnumValue::class), $enumMetadata->values);

        $values = [];

        foreach ($reflectionClass->getConstants() as $name => $value) {
            $reflectionConstant = new ReflectionClassConstant($reflectionClass->getName(), $name);
            $valueConfig = $this->getDescriptionConfiguration($this->getMetadatas($reflectionConstant), true);

            $enumValueAnnotation = current(array_filter($enumValues, fn ($enumValueAnnotation) => $enumValueAnnotation->name === $name));
            $valueConfig['value'] = $value;

            if (false !== $enumValueAnnotation) {
                if (isset($enumValueAnnotation->description)) {
                    $valueConfig['description'] = $enumValueAnnotation->description;
                }

                if (isset($enumValueAnnotation->deprecationReason)) {
                    $valueConfig['deprecationReason'] = $enumValueAnnotation->deprecationReason;
                }
            }

            $values[$name] = $valueConfig;
        }

        $enumConfiguration = ['values' => $values];
        $enumConfiguration = $this->getDescriptionConfiguration($this->getMetadatas($reflectionClass)) + $enumConfiguration;

        return [
            $gqlName => [
                'type' => self::CONFIGURATION_TYPES_MAP[self::TYPE],
                'config' => $enumConfiguration
            ]
        ];
    }
}
