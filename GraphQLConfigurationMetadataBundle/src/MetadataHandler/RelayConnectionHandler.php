<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use \ReflectionClass;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;
use Overblog\GraphQLBundle\Extension\BuilderExtension;

class RelayConnectionHandler extends ObjectHandler
{
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): ?TypeConfigurationInterface
    {
        if (!$reflectionClass->implementsInterface(ConnectionInterface::class)) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" can only be used on class implementing the ConnectionInterface.', $this->formatMetadata('Connection'), $reflectionClass->getName()));
        }

        if (!(isset($typeMetadata->edge) xor isset($typeMetadata->node))) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" is invalid. You must define either the "edge" OR the "node" attribute, but not both.', $this->formatMetadata('Connection'), $reflectionClass->getName()));
        }

        $typeConfiguration = parent::addConfiguration($configuration, $reflectionClass, $typeMetadata);
        $edgeType = $typeMetadata->edge ?? false;
        if (!$edgeType) {
            $edgeType = $typeConfiguration->getName().'Edge';
            $objectConfiguration = new ObjectConfiguration($edgeType);
            $objectConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
                'type' => BuilderExtension::TYPE_FIELDS,
                'name' => 'relay-edge',
                'configuration' => ['nodeType' => $typeMetadata->node]
            ]));
            $configuration->addType($objectConfiguration);
        }

        $typeConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
            'type' => BuilderExtension::TYPE_FIELDS,
            'name' => 'relay-connection',
            'configuration' => ['edgeType' => $edgeType]
        ]));

        return $typeConfiguration;
    }
}