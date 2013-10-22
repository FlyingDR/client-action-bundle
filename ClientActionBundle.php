<?php

namespace Flying\Bundle\ClientActionBundle;

use Flying\Bundle\ClientActionBundle\DependencyInjection\Compiler\RegisterStateSubscribersPass;
use Flying\Bundle\ClientActionBundle\DependencyInjection\Compiler\RegisterActionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ClientActionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterActionsPass());
        $container->addCompilerPass(new RegisterStateSubscribersPass());
    }
}
