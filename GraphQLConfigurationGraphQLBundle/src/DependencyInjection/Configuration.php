<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'overblog_graphql_configuration_graphql';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode
            ->children()
                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('auto_discover')
                            ->treatFalseLike(['bundles' => false, 'root_dir' => false])
                            ->treatTrueLike(['bundles' => true, 'root_dir' => true])
                            ->treatNullLike(['bundles' => true, 'root_dir' => true])
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('root_dir')->defaultValue(false)->end()
                                ->booleanNode('bundles')->defaultValue(false)->end()
                            ->end()
                        ->end()
                        ->arrayNode('directories')
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
