<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataParser;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use \ReflectionClass;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;

class RelayEdgeConfiguration extends TypeConfiguration
{
    public function getConfiguration(ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): array
    {
        if (!$reflectionClass->implementsInterface(EdgeInterface::class)) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" can only be used on class implementing the EdgeInterface.', $this->formatMetadata('Edge'), $reflectionClass->getName()));
        }

        $gqlName = parent::getTypeName($reflectionClass, $typeMetadata);
        $configuration = parent::getConfiguration($reflectionClass, $typeMetadata);
        
        if (!isset($configuration[$gqlName]['builders'])) {
            $configuration[$gqlName]['config']['builders'] = [];
        }
        array_unshift($configuration[$gqlName]['config']['builders'], ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $typeMetadata->node]]);

        return $configuration;
    }
}
