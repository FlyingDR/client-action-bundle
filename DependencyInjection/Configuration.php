<?php

namespace Flying\Bundle\ClientActionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        /** @noinspection PhpUndefinedMethodInspection */
        $tb
            ->root('client_action')
            ->children()
            ->arrayNode('state_nsmap')
            ->requiresAtLeastOneElement()
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('request_params')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('operation')
            ->defaultValue('__operation')
            ->end()
            ->scalarNode('state')
            ->defaultValue('__state')
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $tb;
    }
}
