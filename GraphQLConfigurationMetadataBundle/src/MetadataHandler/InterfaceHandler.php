<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\InterfaceConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use ReflectionClass;

class InterfaceHandler extends ObjectHandler
{
    const TYPE = TypeConfiguration::TYPE_INTERFACE;

    protected function getInterfaceName(ReflectionClass $reflectionClass, Metadata\Metadata $interfaceMetadata): string
    {
        return $interfaceMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $interfaceMetadata): void
    {
        $gqlName = $this->getInterfaceName($reflectionClass, $interfaceMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $interfaceMetadata): ?TypeConfiguration
    {
        $gqlName = $this->getInterfaceName($reflectionClass, $interfaceMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $interfaceConfiguration = InterfaceConfiguration::get($gqlName)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->addExtensions($this->getExtensions($metadatas))
            ->setOrigin($this->getOrigin($reflectionClass));

        $fieldsFromProperties = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $this->getClassProperties($reflectionClass));
        $fieldsFromMethods = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods());

        $fields = $fieldsFromProperties + $fieldsFromMethods;
        foreach ($fields as $field) {
            $interfaceConfiguration->addField($field);
        }

        if (isset($interfaceMetadata->typeResolver)) {
            $interfaceConfiguration->setTypeResolver($this->formatExpression($interfaceMetadata->typeResolver));
        }

        $configuration->addType($interfaceConfiguration);

        return $interfaceConfiguration;
    }
}
