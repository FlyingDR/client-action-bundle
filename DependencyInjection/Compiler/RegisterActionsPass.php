<?php

namespace Flying\Bundle\ClientActionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Container compiler pass to register all available client actions into client actions factory
 */
class RegisterActionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('client_action.factory')) {
            return;
        }
        $definition = $container->getDefinition('client_action.factory');
        $actions = $container->findTaggedServiceIds('client_action.action');
        foreach ($actions as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['action'])) {
                    throw new ParameterNotFoundException('Required parameter "action" is not found for client action service "' . $id . '"');
                }
                $action = $tag['action'];
                $definition->addMethodCall('registerAction', array(new Reference($id), $action));
            }
        }
    }
}
