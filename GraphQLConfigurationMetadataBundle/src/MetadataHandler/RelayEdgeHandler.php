<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use \ReflectionClass;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;
use Overblog\GraphQLBundle\Extension\BuilderExtension;

class RelayEdgeHandler extends ObjectHandler
{
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): ?TypeConfigurationInterface
    {
        if (!$reflectionClass->implementsInterface(EdgeInterface::class)) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" can only be used on class implementing the EdgeInterface.', $this->formatMetadata('Edge'), $reflectionClass->getName()));
        }

        $typeConfiguration = parent::addConfiguration($configuration, $reflectionClass, $typeMetadata);
        $typeConfiguration->addExtension(new ExtensionConfiguration(BuilderExtension::NAME, [
            'type' => BuilderExtension::TYPE_FIELDS,
            'name' => 'relay-edge',
            'configuration' => ['nodeType' => $typeMetadata->node]
        ]));

        return $typeConfiguration;
    }
}
