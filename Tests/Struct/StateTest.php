<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Struct;

use Flying\Bundle\ClientActionBundle\Struct\State;
use Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\MultiLevelState;
use Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\SimpleState;
use Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\TestStateInterface;
use Flying\Tests\TestCase;

class StateTest extends TestCase
{
    public function testStateShouldBeStorable()
    {
        $state = new State();
        $this->assertInstanceOf('Flying\Struct\StorableStruct', $state);
    }

    /**
     * @param string $class
     * @param array $expected
     * @dataProvider dpConvertingToClientRepresentation
     */
    public function testConvertingToClientRepresentation($class, $expected)
    {
        /** @var $state State */
        $state = new $class();
        $this->assertEquals($expected, $state->toClient());
    }

    public function dpConvertingToClientRepresentation()
    {
        return array(
            array(
                'Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\SimpleState',
                array(
                    'name'   => 'John',
                    'age'    => null,
                    'active' => true,
                ),
            ),
            array(
                'Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\MultiLevelState',
                array(
                    'category'                     => 'main',
                    'selected'                     => array(1, 2, 3),
                    'sort.column'                  => 'date',
                    'sort.order'                   => 'desc',
                    'paginator.page'               => 1,
                    'paginator.page_size'          => 20,
                    'synthetic.test'               => 'for',
                    'synthetic.multiple.structure' => 'levels',
                ),
            ),
        );
    }

    /**
     * @param string $class
     * @dataProvider dpReceivingDefaultState
     */
    public function testReceivingDefaultState($class)
    {
        $state = new $class();
        /** @var $state TestStateInterface */
        $expected = $state->getExpectedDefaults();
        /** @var $state State */
        $defaults = $state->getDefaults();
        $this->assertEquals($expected, $defaults);
    }

    public function dpReceivingDefaultState()
    {
        return array(
            array('Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\SimpleState'),
            array('Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\MultiLevelState'),
        );
    }

    public function testModificationsOfSimpleState()
    {
        $state = new SimpleState();
        $this->assertEmpty($state->getModifications());
        $state->name = 'Paul';
        $state->set(array(
            'age'    => 35,
            'active' => false,
        ));
        $m = $state->getModifications();
        $this->assertEquals(array(
            'name'   => 'Paul',
            'age'    => 35,
            'active' => false,
        ), $m);
        $state->getProperty('age')->reset();
        $this->assertEquals(array(
            'name'   => 'Paul',
            'active' => false,
        ), $state->getModifications());
        $state->reset();
        $this->assertEmpty($state->getModifications());
    }

    public function testModificationsOfMultiLevelState()
    {
        $state = new MultiLevelState();
        $this->assertEmpty($state->getModifications());
        $state->category = 'another';
        $state->sort->set(array(
            'column' => 'name',
            'order'  => 'asc',
        ));
        $state->paginator->page = 5;
        $state->synthetic->multiple->structure = 'changed';
        $this->assertEquals(array(
            'category'  => 'another',
            'sort'      => array(
                'column' => 'name',
                'order'  => 'asc',
            ),
            'paginator' => array(
                'page' => 5,
            ),
            'synthetic' => array(
                'multiple' => array(
                    'structure' => 'changed',
                ),
            ),
        ), $state->getModifications());
        $state->getProperty('category')->reset();
        $state->sort->order = 'desc';
        $state->synthetic->multiple->getProperty('structure')->reset();
        $this->assertEquals(array(
            'sort'      => array(
                'column' => 'name',
            ),
            'paginator' => array(
                'page' => 5,
            ),
        ), $state->getModifications());
        $state->reset();
        $this->assertEmpty($state->getModifications());
    }
}
