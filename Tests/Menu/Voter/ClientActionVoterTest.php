<?php
namespace Flying\Bundle\ClientActionBundle\Tests\Menu\Voter;

use Flying\Bundle\ClientActionBundle\Menu\Factory\ClientActionExtension;
use Flying\Bundle\ClientActionBundle\Menu\Voter\ClientActionVoter;
use Flying\Bundle\ClientActionBundle\Struct\ClientAction;
use Flying\Bundle\ClientActionBundle\Struct\State;
use Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\MultiLevelState;
use Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures\SimpleState;
use Flying\Bundle\ClientActionBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;

class ClientActionVoterTest extends TestCase
{
    /**
     * Menu items factory
     * @var MenuFactory
     */
    protected $factory;

    public function testInterfaces()
    {
        $voter = $this->getTestClass();
        $this->assertInstanceOf('Knp\Menu\Matcher\Voter\VoterInterface', $voter);
    }

    public function testNonClientActionItemMatching()
    {
        $voter = $this->getTestClass();
        $item = $this->getMenuFactory()->createItem('test');
        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @param State $state
     * @param ClientAction $ca
     * @param mixed $expected
     * @dataProvider dpItemMatching
     */
    public function testItemMatching(State $state, ClientAction $ca, $expected)
    {
        $voter = $this->getTestClass($state);
        $item = $this->getMenuItem($ca);
        $actual = $voter->matchItem($item);
        $this->assertSame($expected, $actual);
    }

    public function dpItemMatching()
    {
        return array(
            array(
                new SimpleState(),
                new ClientAction(),
                null,
            ),
            array(
                new SimpleState(),
                new ClientAction('state:#name=John&active=true'),
                true,
            ),
            array(
                new SimpleState(),
                new ClientAction('state:#active=null'),
                false,
            ),
            array(
                new SimpleState(array('name' => 'Paul', 'active' => null)),
                new ClientAction('state:#active=null'),
                true,
            ),
            array(
                new SimpleState(array('name' => 'Paul', 'active' => null, 'age' => 45)),
                new ClientAction('event:someEvent?age=123&active=disabled#active=null&name=Paul'),
                true,
            ),
            array(
                new MultiLevelState(),
                new ClientAction('state:#category=main&sort.column=date&sort.order=desc'),
                true,
            ),
            array(
                new MultiLevelState(),
                new ClientAction('state:#category=main&sort.column=date&sort.order=desc&unknown=value'),
                false,
            ),
            array(
                new MultiLevelState(),
                new ClientAction('state:#selected=[1,3]'),
                false,
            ),
            array(
                new MultiLevelState(),
                new ClientAction('state:#selected=[1,2,3]'),
                true,
            ),
            array(
                new MultiLevelState(),
                new ClientAction('state:#selected=[3,2,1]'),
                true,
            ),
        );
    }

    /**
     * @param State $state
     * @return ClientActionVoter
     */
    protected function getTestClass(State $state = null)
    {
        if (!$state) {
            $state = new State();
        }
        return new ClientActionVoter($state);
    }

    /**
     * @return MenuFactory
     */
    protected function getMenuFactory()
    {
        if (!$this->factory) {
            $this->factory = new MenuFactory();
            $this->factory->addExtension(new ClientActionExtension());
        }
        return $this->factory;
    }

    /**
     * @param ClientAction $ca
     * @return MenuItem
     */
    protected function getMenuItem(ClientAction $ca)
    {
        $item = $this->getMenuFactory()->createItem('test', array('ca' => $ca));
        return $item;
    }
}
