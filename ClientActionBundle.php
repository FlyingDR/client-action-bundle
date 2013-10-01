<?php

namespace Flying\Bundle\ClientActionBundle;

use Flying\Bundle\ClientActionBundle\DependencyInjection\Compiler\RegisterStateSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ClientActionBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterStateSubscribersPass());
    }
}
