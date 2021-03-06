<?php

namespace Flying\Bundle\ClientActionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ClientActionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('client_action.request_parameters.operation', $config['request_params']['operation']);
        $container->setParameter('client_action.request_parameters.state', $config['request_params']['state']);
        if (count($config['state_nsmap'])) {
            $container->setParameter('client_action.state.nsmap.namespaces', $config['state_nsmap']);
        }
    }
}
