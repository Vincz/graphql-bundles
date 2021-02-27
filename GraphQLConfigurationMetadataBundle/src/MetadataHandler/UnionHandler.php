<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use Overblog\GraphQLBundle\Configuration\UnionConfiguration;
use ReflectionClass;

class UnionHandler extends MetadataHandler
{
    const TYPE = TypeConfiguration::TYPE_UNION;

    protected function getUnionName(ReflectionClass $reflectionClass, Metadata\Metadata $unionMetadata): string
    {
        return $unionMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $unionMetadata): void
    {
        $gqlName = $this->getUnionName($reflectionClass, $unionMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Get a GraphQL scalar configuration from given scalar metadata.
     *
     * @return array{type: 'custom-scalar', config: array}
     */
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $unionMetadata): ?TypeConfiguration
    {
        $gqlName = $this->getUnionName($reflectionClass, $unionMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $unionConfiguration = UnionConfiguration::get($gqlName)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->addExtensions($this->getExtensions($metadatas))
            ->setOrigin($this->getOrigin($reflectionClass));

        if (!empty($unionMetadata->types)) {
            $types = $unionMetadata->types;
        } else {
            $types = array_keys($this->classesTypesMap->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                $typeClassName = $configuration['class'];
                $typeMetadata = new ReflectionClass($typeClassName);

                if ($reflectionClass->isInterface() && $typeMetadata->implementsInterface($reflectionClass->getName())) {
                    return true;
                }

                return $typeMetadata->isSubclassOf($reflectionClass->getName());
            }, TypeConfiguration::TYPE_OBJECT));
            sort($types);
        }
        $unionConfiguration->setTypes($types);

        if (isset($unionMetadata->typeResolver)) {
            $typeResolver = $this->formatExpression($unionMetadata->typeResolver);
        } else {
            if ($reflectionClass->hasMethod('resolveType')) {
                $method = $reflectionClass->getMethod('resolveType');
                if ($method->isStatic() && $method->isPublic()) {
                    $typeResolver = $this->formatExpression(sprintf("@=call('%s::%s', [service('overblog_graphql.type_resolver'), value], true)", $this->formatNamespaceForExpression($reflectionClass->getName()), 'resolveType'));
                } else {
                    throw new MetadataConfigurationException(sprintf('The "resolveType()" method on class must be static and public. Or you must define a "resolveType" attribute on the %s metadata.', $this->formatMetadata('Union')));
                }
            } else {
                throw new MetadataConfigurationException(sprintf('The metadata %s has no "resolveType" attribute and the related class has no "resolveType()" public static method. You need to define one of them.', $this->formatMetadata('Union')));
            }
        }
        $unionConfiguration->setTypeResolver($typeResolver);

        $configuration->addType($unionConfiguration);

        return $unionConfiguration;
    }
}
