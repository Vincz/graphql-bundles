<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation as Meta;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use ReflectionClass;

class UnionConfiguration extends MetadataConfiguration
{
    const TYPE = self::GQL_UNION;

    protected function getUnionName(ReflectionClass $reflectionClass, Meta $unionMetadata): string
    {
        return $unionMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Meta $unionMetadata): void
    {
        $gqlName = $this->getUnionName($reflectionClass, $unionMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Get a GraphQL scalar configuration from given scalar metadata.
     *
     * @return array{type: 'custom-scalar', config: array}
     */
    public function getConfiguration(ReflectionClass $reflectionClass, Meta $unionMetadata): array
    {
        $gqlName = $this->getUnionName($reflectionClass, $unionMetadata);
        $unionConfiguration = [];
        if (!empty($unionMetadata->types)) {
            $unionConfiguration['types'] = $unionMetadata->types;
        } else {
            $types = array_keys($this->classesTypesMap->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                $typeClassName = $configuration['class'];
                $typeMetadata = new ReflectionClass($typeClassName);

                if ($reflectionClass->isInterface() && $typeMetadata->implementsInterface($reflectionClass->getName())) {
                    return true;
                }

                return $typeMetadata->isSubclassOf($reflectionClass->getName());
            }, self::GQL_TYPE));
            sort($types);
            $unionConfiguration['types'] = $types;
        }

        $unionConfiguration = $this->getDescriptionConfiguration($this->getMetadatas($reflectionClass)) + $unionConfiguration;

        if (isset($unionMetadata->resolveType)) {
            $unionConfiguration['resolveType'] = $this->formatExpression($unionMetadata->resolveType);
        } else {
            if ($reflectionClass->hasMethod('resolveType')) {
                $method = $reflectionClass->getMethod('resolveType');
                if ($method->isStatic() && $method->isPublic()) {
                    $unionConfiguration['resolveType'] = $this->formatExpression(sprintf("@=call('%s::%s', [service('overblog_graphql.type_resolver'), value], true)", $this->formatNamespaceForExpression($reflectionClass->getName()), 'resolveType'));
                } else {
                    throw new MetadataConfigurationException(sprintf('The "resolveType()" method on class must be static and public. Or you must define a "resolveType" attribute on the %s metadata.', $this->formatMetadata('Union')));
                }
            } else {
                throw new MetadataConfigurationException(sprintf('The metadata %s has no "resolveType" attribute and the related class has no "resolveType()" public static method. You need to define one of them.', $this->formatMetadata('Union')));
            }
        }

        return [
            $gqlName => [
                'type' => self::CONFIGURATION_TYPES_MAP[self::TYPE],
                'config' => $unionConfiguration,
            ],
        ];
    }
}
