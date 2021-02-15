<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'overblog_graphql.configuration_yaml';

    /**
     * @param bool $debug Whether to use the debug mode
     */
    public function __construct(bool $debug, string $cacheDir = null)
    {
        $this->debug = (bool) $debug;
        $this->cacheDir = $cacheDir;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode
            ->children()
                ->scalarNode('coucou')->end()
            ->end();

        return $treeBuilder;
    }
}
