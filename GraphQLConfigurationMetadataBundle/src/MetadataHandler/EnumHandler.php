<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\EnumConfiguration;
use Overblog\GraphQLBundle\Configuration\EnumValueConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;
use ReflectionClass;
use ReflectionClassConstant;

class EnumHandler extends MetadataHandler
{
    const TYPE = TypeConfigurationInterface::TYPE_ENUM;

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
     * Add a GraphQL Union configuration from given union metadata.
     */
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $enumMetadata): ?TypeConfigurationInterface
    {
        $gqlName = $this->getEnumName($reflectionClass, $enumMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $enumConfiguration = new EnumConfiguration($gqlName);
        $enumConfiguration->setDescription($this->getDescription($metadatas));
        $enumConfiguration->setDeprecation($this->getDeprecation($metadatas));

        // Annotation @EnumValue handling
        $enumValues = array_merge($this->getMetadataMatching($metadatas, Metadata\EnumValue::class), $enumMetadata->values);

        foreach ($reflectionClass->getConstants() as $name => $value) {
            $reflectionConstant = new ReflectionClassConstant($reflectionClass->getName(), $name);
            $valueMetadatas = $this->getMetadatas($reflectionConstant);

            $enumValueConfig = new EnumValueConfiguration($name, $value);
            $enumValueConfig->setDescription($this->getDescription($valueMetadatas));
            $enumValueConfig->setDeprecation($this->getDeprecation($valueMetadatas));

            // Search matching @EnumValue handling
            $enumValueAnnotation = current(array_filter($enumValues, fn ($enumValueAnnotation) => $enumValueAnnotation->name === $name));

            if (false !== $enumValueAnnotation) {
                if (isset($enumValueAnnotation->description)) {
                    $enumValueConfig->setDescription($enumValueAnnotation->description);
                }

                if (isset($enumValueAnnotation->deprecationReason)) {
                    $enumValueConfig->setDeprecation($enumValueAnnotation->deprecationReason);
                }
            }

            $enumConfiguration->addValue($enumValueConfig);
        }
        
        $configuration->addType($enumConfiguration);

        return $enumConfiguration;
    }
}
