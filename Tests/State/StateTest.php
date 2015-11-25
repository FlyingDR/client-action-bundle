<?php

namespace Flying\Bundle\ClientActionBundle\Tests\State;

use Flying\Bundle\ClientActionBundle\State\State;
use Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\MultiLevelState;
use Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\SimpleState;
use Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\TestStateInterface;
use Flying\Tests\TestCase;

class StateTest extends TestCase
{
    public function testStateShouldBeStorable()
    {
        $state = new State();
        static::assertInstanceOf('Flying\Struct\StorableStruct', $state);
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
        static::assertEquals($expected, $state->toClient());
    }

    public function dpConvertingToClientRepresentation()
    {
        return array(
            array(
                'Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\SimpleState',
                array(
                    'name'   => 'John',
                    'age'    => null,
                    'active' => true,
                ),
            ),
            array(
                'Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\MultiLevelState',
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
        static::assertEquals($expected, $defaults);
    }

    public function dpReceivingDefaultState()
    {
        return array(
            array('Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\SimpleState'),
            array('Flying\Bundle\ClientActionBundle\Tests\State\Fixtures\MultiLevelState'),
        );
    }

    public function testModificationsOfSimpleState()
    {
        $state = new SimpleState();
        static::assertEmpty($state->getModifications());
        $state->name = 'Paul';
        $state->set(array(
            'age'    => 35,
            'active' => false,
        ));
        $m = $state->getModifications();
        static::assertEquals(array(
            'name'   => 'Paul',
            'age'    => 35,
            'active' => false,
        ), $m);
        $state->getProperty('age')->reset();
        static::assertEquals(array(
            'name'   => 'Paul',
            'active' => false,
        ), $state->getModifications());
        $state->reset();
        static::assertEmpty($state->getModifications());
    }

    public function testModificationsOfMultiLevelState()
    {
        $state = new MultiLevelState();
        static::assertEmpty($state->getModifications());
        $state->category = 'another';
        $state->sort->set(array(
            'column' => 'name',
            'order'  => 'asc',
        ));
        $state->paginator->page = 5;
        $state->synthetic->multiple->structure = 'changed';
        static::assertEquals(array(
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
        static::assertEquals(array(
            'sort'      => array(
                'column' => 'name',
            ),
            'paginator' => array(
                'page' => 5,
            ),
        ), $state->getModifications());
        $state->reset();
        static::assertEmpty($state->getModifications());
    }
}
