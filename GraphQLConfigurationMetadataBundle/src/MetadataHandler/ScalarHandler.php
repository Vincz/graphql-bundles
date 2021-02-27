<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ScalarConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use ReflectionClass;

class ScalarHandler extends MetadataHandler
{
    const TYPE = TypeConfiguration::TYPE_SCALAR;

    protected function getScalarName(ReflectionClass $reflectionClass, Metadata\Metadata $scalarMetadata): string
    {
        return $scalarMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $scalarMetadata): void
    {
        $gqlName = $this->getScalarName($reflectionClass, $scalarMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Get a GraphQL scalar configuration from given scalar metadata.
     *
     * @return array{type: 'custom-scalar', config: array}
     */
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $scalarMetadata): ?TypeConfiguration
    {
        $gqlName = $this->getScalarName($reflectionClass, $scalarMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $scalarConfiguration = ScalarConfiguration::get($gqlName)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->addExtensions($this->getExtensions($metadatas))
            ->setOrigin($this->getOrigin($reflectionClass));

        if (isset($scalarMetadata->scalarType)) {
            $scalarConfiguration->setScalarType($this->formatExpression($scalarMetadata->scalarType));
        } else {
            $scalarConfiguration->setSerialize(sprintf('%s::%s', $reflectionClass->getName(), 'serialize'));
            $scalarConfiguration->setParseValue(sprintf('%s::%s', $reflectionClass->getName(), 'parseValue'));
            $scalarConfiguration->setParseLiteral(sprintf('%s::%s', $reflectionClass->getName(), 'parseLiteral'));
        }

        $configuration->addType($scalarConfiguration);

        return $scalarConfiguration;
    }
}
