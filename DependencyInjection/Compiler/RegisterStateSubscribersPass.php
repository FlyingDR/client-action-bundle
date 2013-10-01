<?php

namespace Flying\Bundle\ClientActionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Container compiler pass to register services
 * that want to receive current application state
 * as soon as it will be determined
 */
class RegisterStateSubscribersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('client_action.state.initializer')) {
            return;
        }
        $definition = $container->getDefinition('client_action.state.initializer');
        $subscribers = $container->findTaggedServiceIds('client_action.state.subscriber');
        foreach ($subscribers as $id => $tags) {
            $definition->addMethodCall('addStateSubscriber', array(new Reference($id)));
        }
    }
}
