<?php

namespace Flying\Bundle\ClientActionBundle\Tests;

use Flying\Bundle\ClientActionBundle\Factory\ClientActionFactory;

/**
 * Testcase that requires client actions factory
 */
class TestCaseUsingFactory extends TestCase
{
    /**
     * List of client action classes to use for test
     *
     * @var array
     */
    protected $caClassesList = array(
        'state' => 'Flying\Bundle\ClientActionBundle\ClientAction\StateClientAction',
        'event' => 'Flying\Bundle\ClientActionBundle\ClientAction\EventClientAction',
        'load'  => 'Flying\Bundle\ClientActionBundle\ClientAction\LoadClientAction',
    );

    /**
     * @return ClientActionFactory
     */
    public function getTestFactory()
    {
        $factory = new ClientActionFactory();
        foreach ($this->caClassesList as $type => $class) {
            $ca = new $class();
            $factory->registerAction($ca, $type);
        }
        return $factory;
    }
}
