<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Factory;

use Flying\Bundle\ClientActionBundle\ClientAction\StateClientAction;
use Flying\Bundle\ClientActionBundle\Tests\TestCaseUsingFactory;
use Flying\Struct\Common\ComplexPropertyInterface;

class ClientActionFactoryTest extends TestCaseUsingFactory
{
    /**
     * @var array
     */
    protected $tests = array(
        array(
            'state',
            'state:modify?a=b&c=d',
            array(
                'action'    => 'state',
                'target'    => null,
                'args'      => array(),
                'operation' => 'modify',
                'state'     => array(
                    'a' => 'b',
                    'c' => 'd',
                ),
            ),
        ),
        array(
            'event',
            'event:[.someTarget]testEventName?a=b&c=d#q=w&e=r',
            array(
                'action'    => 'event',
                'target'    => '.someTarget',
                'event'     => 'testEventName',
                'args'      => array(
                    'a' => 'b',
                    'c' => 'd',
                ),
                'operation' => 'modify',
                'state'     => array(
                    'q' => 'w',
                    'e' => 'r',
                ),
            ),
        ),
        array(
            'load',
            'load:[.someTarget]/some/url?a=b&c=d#q=w&e=r',
            array(
                'action'    => 'load',
                'target'    => '.someTarget',
                'url'       => '/some/url',
                'args'      => array(
                    'a' => 'b',
                    'c' => 'd',
                ),
                'operation' => 'modify',
                'state'     => array(
                    'q' => 'w',
                    'e' => 'r',
                ),
            ),
        ),
    );

    /**
     * @param mixed $info
     * @param string $class
     * @param array $expected
     * @dataProvider dpCreationUsingFactory
     */
    public function testCreationUsingFactory($info, $class, array $expected = null)
    {
        $factory = $this->getTestFactory();
        $ca = $factory->create($info);
        $this->assertInstanceOf($class, $ca);
        foreach ($ca as $name => $value) {
            if (array_key_exists($name, $expected)) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                $this->assertEquals($expected[$name], $value);
            }
        }
    }

    public function dpCreationUsingFactory()
    {
        $tests = array();
        foreach ($this->tests as $test) {
            $type = array_shift($test);
            $string = array_shift($test);
            $array = array_shift($test);
            $class = $this->caClassesList[$type];
            $tests[] = array(
                $string, $class, $array,
            );
            $tests[] = array(
                $array, $class, $array,
            );
            $tests[] = array(
                new $class($string), $class, $array,
            );
        }
        return $tests;
    }

    /**
     * @param mixed $info
     * @param boolean $valid
     * @dataProvider dpValidSourceInformationTypes
     */
    public function testValidSourceInformationTypes($info, $valid = true)
    {
        $factory = $this->getTestFactory();
        if (!$valid) {
            $this->setExpectedException('\InvalidArgumentException');
        }
        $factory->create($info);
    }

    public function dpValidSourceInformationTypes()
    {
        return array(
            array('state:modify?a=b&c=d', true),
            array(
                array(
                    'action'    => 'state',
                    'operation' => 'modify',
                    'state'     => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                ),
                true
            ),
            array(new StateClientAction(), true),
            array(null, false),
            array(true, false),
            array(false, false),
            array(12345, false),
            array('abc', false),
            array(new \ArrayObject(), false),
            array(new \DateTime(), false),
        );
    }

    public function testOnlyRegisteredClientActionTypesCanBeCreatedByFactory()
    {
        $factory = $this->getTestFactory();
        $this->setExpectedException('\InvalidArgumentException', 'Unknown client action type: unknown');
        $factory->create('unknown:action');
    }


}
