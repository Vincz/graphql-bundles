<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use const PHP_VERSION_ID;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'overblog_graphql_configuration_metadata';
    public const READER_ANNOTATION = 'annotation';
    public const READER_ATTRIBUTE = 'attribute';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode
            ->children()
                ->enumNode('reader')
                    ->defaultValue(PHP_VERSION_ID < 80000 ? self::READER_ANNOTATION : self::READER_ATTRIBUTE)
                    ->values([self::READER_ANNOTATION, self::READER_ATTRIBUTE])
                ->end()
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
                ->arrayNode('type_guessing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('doctrine')
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
