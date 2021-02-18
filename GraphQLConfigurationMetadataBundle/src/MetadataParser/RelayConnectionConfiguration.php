<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataParser;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use \ReflectionClass;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;

class RelayConnectionConfiguration extends TypeConfiguration
{
    public function getConfiguration(ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): array
    {
        if (!$reflectionClass->implementsInterface(ConnectionInterface::class)) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" can only be used on class implementing the ConnectionInterface.', $this->formatMetadata('Connection'), $reflectionClass->getName()));
        }

        if (!(isset($typeMetadata->edge) xor isset($typeMetadata->node))) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" is invalid. You must define either the "edge" OR the "node" attribute, but not both.', $this->formatMetadata('Connection'), $reflectionClass->getName()));
        }

        $gqlName = parent::getTypeName($reflectionClass, $typeMetadata);
        $configuration = parent::getConfiguration($reflectionClass, $typeMetadata);

        $edgeType = $typeMetadata->edge ?? false;
        if (!$edgeType) {
            $edgeType = $gqlName.'Edge';
            $configuration[$edgeType] = [
                'type' => 'object',
                'config' => [
                    'builders' => [
                        ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $typeMetadata->node]],
                    ],
                ],
            ];
        }

        if (!isset($configuration[$gqlName]['config']['builders'])) {
            $configuration[$gqlName]['config']['builders'] = [];
        }

        array_unshift($configuration[$gqlName]['config']['builders'], ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => $edgeType]]);

        return $configuration;
    }
}
