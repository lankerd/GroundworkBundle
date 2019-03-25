<?php

namespace Lankerd\GroundworkBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class LankerdGroundworkExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $configuration = $this->processConfiguration($configuration, $configs);


        /**
         * lankerd_groundwork.import_services
         * We will set a parameter that'll hold a list
         * of the import service(s) the user provided
         */

        try {
            $container->setParameter($this->getAlias().'.import_services', $configuration['import_services']);
        } catch (\Exception $e) {
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }
}
