<?php

namespace Lankerd\GroundworkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lankerd_groundwork');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->variableNode('import_services')->defaultValue('NULL')->info('A list of entities intended on being imported')->end()
            ->end();

        return $treeBuilder;
    }
}
