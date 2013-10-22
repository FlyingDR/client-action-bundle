<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Menu\Factory;

use Flying\Bundle\ClientActionBundle\ClientAction\StateClientAction;
use Flying\Bundle\ClientActionBundle\Menu\Factory\ClientActionExtension;
use Flying\Bundle\ClientActionBundle\Tests\TestCase;
use Knp\Menu\ItemInterface;
use Mockery;

class ClientActionExtensionTest extends TestCase
{
    public function testInterfaces()
    {
        $extension = $this->getTestClass();
        $this->assertInstanceOf('Knp\Menu\Factory\ExtensionInterface', $extension);
    }

    /**
     * @param array $options
     * @param array $expected
     * @dataProvider dpBuildOptions
     */
    public function testBuildOptions(array $options, array $expected)
    {
        $extension = $this->getTestClass();
        $actual = $extension->buildOptions($options);
        $this->assertEquals($expected, $actual);
    }

    public function dpBuildOptions()
    {
        return array(
            array(
                array(),
                array(
                    'extras' => array(
                        'ca' => null,
                    ),
                ),
            ),
            array(
                array(
                    'some' => 'value',
                ),
                array(
                    'some'   => 'value',
                    'extras' => array(
                        'ca' => null,
                    ),
                ),
            ),
            array(
                array(
                    'some'   => 'value',
                    'extras' => array(
                        'xyz' => 123,
                    ),
                ),
                array(
                    'some'   => 'value',
                    'extras' => array(
                        'xyz' => 123,
                        'ca'  => null,
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $options
     * @param boolean $haveCa
     * @dataProvider dpBuildItem
     */
    public function testBuildItem(array $options, $haveCa = false)
    {
        $extension = $this->getTestClass();
        $item = Mockery::mock('Knp\Menu\ItemInterface');
        if ($haveCa) {
            $item->shouldReceive('setExtra')->once()
                ->with('ca', Mockery::type('Flying\Bundle\ClientActionBundle\ClientAction\ClientAction'))->getMock();
        }
        /** @var $item ItemInterface */
        $extension->buildItem($item, $options);
    }

    public function dpBuildItem()
    {
        return array(
            array(
                array(),
                false,
            ),
            array(
                array(
                    'some' => 'option',
                ),
                false,
            ),
            array(
                array(
                    'ca' => new StateClientAction(),
                ),
                true,
            ),
            array(
                array(
                    'some' => 'option',
                    'ca'   => new StateClientAction(),
                ),
                true,
            ),
            array(
                array(
                    'some'   => 'option',
                    'extras' => array(
                        'xyz' => 123,
                    ),
                ),
                false,
            ),
            array(
                array(
                    'some'   => 'option',
                    'extras' => array(
                        'ca'  => new StateClientAction(),
                        'xyz' => 123,
                    ),
                ),
                true,
            ),
        );
    }

    /**
     * @return ClientActionExtension
     */
    protected function getTestClass()
    {
        return new ClientActionExtension();
    }
}
