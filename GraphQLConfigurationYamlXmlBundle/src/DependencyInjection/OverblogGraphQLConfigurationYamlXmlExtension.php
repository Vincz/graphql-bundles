<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\DependencyInjection;

use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLConfigurationYamlXmlExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        foreach (['yaml', 'xml'] as $type) {
            if (isset($config[$type])) {
                $loader->load('services_'.$type.'.yaml');
                $directories = $this->resolveMappingDirectories($container, $config[$type]['mapping']);
                $container->setParameter('graphql.configuration.directories.'.$type, $directories);
            }
        }
    }

    protected function resolveMappingDirectories(ContainerBuilder $container, array $config): array
    {
        $rootDirectory = $container->getParameter('kernel.project_dir');
        $bundles = $container->getParameter('kernel.bundles');

        $directories = [];
        if ($config['auto_discover']['root_dir']) {
            $directories[] = sprintf('%s/config/graphql', $rootDirectory);
        }
        if ($config['auto_discover']['bundles']) {
            foreach ($bundles as $bundleClass) {
                $directories[] = sprintf('%s/config/graphql', $this->resolveBundleDirectory($bundleClass));
            }
        }

        return [...$directories, ...$config['directories']];
    }

    protected function resolveBundleDirectory(string $bundleClass)
    {
        return dirname((new ReflectionClass($bundleClass))->getFileName());
    }

    public function getAlias(): string
    {
        return Configuration::NAME;
    }
}
