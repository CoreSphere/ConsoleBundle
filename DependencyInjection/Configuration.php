<?php

namespace CoreSphere\ConsoleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package CoreSphere\ConsoleBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('core_sphere_console');

        $rootNode
            ->children()
                ->arrayNode('filtering')
                    ->children()
                        ->arrayNode('whitelist')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('blacklist')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
