<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use ReflectionClass;

class RelayEdgeHandler extends ObjectHandler
{
    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): ?TypeConfiguration
    {
        if (!$reflectionClass->implementsInterface(EdgeInterface::class)) {
            throw new MetadataConfigurationException(sprintf('The metadata %s on class "%s" can only be used on class implementing the EdgeInterface.', $this->formatMetadata('Edge'), $reflectionClass->getName()));
        }

        $typeConfiguration = parent::addConfiguration($configuration, $reflectionClass, $typeMetadata);
        if (null !== $typeConfiguration) {
            /** @var ObjectConfiguration $typeConfiguration */
            $typeConfiguration->addExtension(ExtensionConfiguration::get(BuilderExtension::ALIAS, [
                'name' => 'relay-edge',
                'configuration' => ['nodeType' => $typeMetadata->node],
            ]));
        }

        return $typeConfiguration;
    }
}
