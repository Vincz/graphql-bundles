<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation as Meta;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as Metadata;
use ReflectionClass;

class ScalarConfiguration extends MetadataConfiguration
{
    const TYPE = self::GQL_SCALAR;

    protected function getScalarName(ReflectionClass $reflectionClass, Meta $scalarMetadata): string
    {
        return $scalarMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Meta $scalarMetadata): void
    {
        $gqlName = $this->getScalarName($reflectionClass, $scalarMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Get a GraphQL scalar configuration from given scalar metadata.
     *
     * @return array{type: 'custom-scalar', config: array}
     */
    public function getConfiguration(ReflectionClass $reflectionClass, Meta $scalarMetadata): array
    {
        $gqlName = $this->getScalarName($reflectionClass, $scalarMetadata);
        $scalarConfiguration = [];

        if (isset($scalarMetadata->scalarType)) {
            $scalarConfiguration['scalarType'] = $this->formatExpression($scalarMetadata->scalarType);
        } else {
            $scalarConfiguration = [
                'serialize' => [$reflectionClass->getName(), 'serialize'],
                'parseValue' => [$reflectionClass->getName(), 'parseValue'],
                'parseLiteral' => [$reflectionClass->getName(), 'parseLiteral'],
            ];
        }

        $scalarConfiguration = $this->getDescriptionConfiguration($this->getMetadatas($reflectionClass)) + $scalarConfiguration;

        return [
            $gqlName => [
                'type' => self::CONFIGURATION_TYPES_MAP[self::TYPE],
                'config' => $scalarConfiguration,
            ],
        ];
    }
}
