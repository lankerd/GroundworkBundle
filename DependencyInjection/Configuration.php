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

        $treeBuilder->getRootNode()
            ->fixXmlConfig('import_service')
            ->children()
                ->variableNode('import_services')->end()
            ->end();

        return $treeBuilder;
    }
}
