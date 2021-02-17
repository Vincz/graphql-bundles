<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use const PHP_VERSION_ID;

class OverblogGraphQLConfigurationMetadataExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        
        switch ($config['reader']) {
            case Configuration::READER_ANNOTATION:
                $readerService = 'Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\AnnotationReader';
                break;
            case Configuration::READER_ATTRIBUTE:
                if (PHP_VERSION_ID < 80000) {
                    throw new InvalidConfigurationException("The attribute metadata reader is only availabe with PHP >= 8.0.");
                }
    
                $readerService = 'Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\AttributeReader';
                break;
        }
        
        // Set metadata reader alias
        $container->setAlias('overblog_graphql.metadata.reader', $readerService);

        // Add mapping directories
        $container->setParameter("graphql.configuration.directories.metadata", $config['mapping']['directories']);
        
        // Add doctrine mapping for Doctrine type guesser
        $container->setParameter("graphql.configuration.metadata.type_guesser.doctrine_mapping", $config['type_guessing']['doctrine']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('type_guesser.yaml');
        $loader->load('services.yaml');
        $loader->load('metadata.yaml');
    }

    public function getAlias(): string
    {
        return Configuration::NAME;
    }
}
