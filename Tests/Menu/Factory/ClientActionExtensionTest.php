<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Menu\Factory;

use Flying\Bundle\ClientActionBundle\Menu\Factory\ClientActionExtension;
use Flying\Bundle\ClientActionBundle\Tests\TestCaseUsingFactory;
use Knp\Menu\ItemInterface;
use Mockery;

class ClientActionExtensionTest extends TestCaseUsingFactory
{
    public function testInterfaces()
    {
        $extension = $this->getTestClass();
        static::assertInstanceOf('Knp\Menu\Factory\ExtensionInterface', $extension);
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
        static::assertEquals($expected, $actual);
    }

    public function dpBuildOptions()
    {
        return array(
            array(
                array(),
                array(
                    'extras' => array(
                        'client_action' => null,
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
                        'client_action' => null,
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
                        'xyz'           => 123,
                        'client_action' => null,
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
        $item = Mockery::mock('Knp\Menu\ItemInterface')->shouldIgnoreMissing();
        if ($haveCa) {
            $item = $item->shouldReceive('setExtra')->once()
                ->with('client_action', Mockery::type('Flying\Bundle\ClientActionBundle\ClientAction\ClientAction'))
                ->getMock()
                ->shouldReceive('getLabelAttributes')->once()
                ->andReturn(array())
                ->getMock();
        }
        /** @var $item ItemInterface */
        $options = $extension->buildOptions($options);
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
                    'ca' => 'state:modify?a=b',
                ),
                true,
            ),
            array(
                array(
                    'some'          => 'option',
                    'client_action' => 'load:/some/url',
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
                        'ca'  => 'event:someEvent?a=b',
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
        return new ClientActionExtension($this->getTestFactory());
    }
}
