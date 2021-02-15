<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation as Meta;
use ReflectionClass;

class InterfaceConfiguration extends TypeConfiguration
{
    const TYPE = self::GQL_INTERFACE;

    protected function getInterfaceName(ReflectionClass $reflectionClass, Meta $interfaceMetadata): string
    {
        return $interfaceMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Meta $interfaceMetadata): void
    {
        $gqlName = $this->getInterfaceName($reflectionClass, $interfaceMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    public function getConfiguration(ReflectionClass $reflectionClass, Meta $interfaceMetadata): array
    {
        $gqlName = $this->getInterfaceName($reflectionClass, $interfaceMetadata);
        $interfaceConfiguration = [];

        $fieldsFromProperties = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $this->getClassProperties($reflectionClass));
        $fieldsFromMethods = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods());

        $interfaceConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $interfaceConfiguration = $this->getDescriptionConfiguration($this->getMetadatas($reflectionClass)) + $interfaceConfiguration;

        $interfaceConfiguration['resolveType'] = $this->formatExpression($interfaceMetadata->resolveType);

        return [
            $gqlName => [
                'type' => self::CONFIGURATION_TYPES_MAP[self::TYPE],
                'config' => $interfaceConfiguration,
            ],
        ];
    }
}
