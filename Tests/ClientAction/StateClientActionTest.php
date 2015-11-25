<?php

namespace Flying\Bundle\ClientActionBundle\Tests\ClientAction;

use Flying\Bundle\ClientActionBundle\ClientAction\StateClientAction;

class StateClientActionTest extends ClientActionTest
{
    /**
     * {@inheritdoc}
     */
    public function dpGetModified()
    {
        // @TODO Implement data provider for client action modifications
        $ca = 'state:modify?a=b&c=d&t=[1,2,3]';
        return array(
            array($ca, array()),
        );
    }

    /**
     * @param string $value
     * @param mixed $expected
     * @dataProvider dpValuesConversion
     */
    public function testValuesConversion($value, $expected)
    {
        $ca = $this->getTestClientAction();
        $action = $ca->action;
        $ca = $this->getTestClientAction($action . ':#arg=' . $value);
        static::assertSame($expected, $ca->state['arg']);
        $ca = $this->getTestClientAction($action . ':#arg[test]=' . $value);
        static::assertSame($expected, $ca->state['arg']['test']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTestDataSets()
    {
        return array(
            array(
                'source'   => array(
                    'array'  => array(
                        'action' => 'state'
                    ),
                    'string' => 'state',
                ),
                'expected' => array(
                    'valid' => false,
                    'array' => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'modify',
                        'state'     => array(),
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action' => 'some invalid action type'
                    ),
                    'string' => 'some invalid action type',
                ),
                'expected' => array(
                    'valid' => false,
                    'array' => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'modify',
                        'state'     => array(),
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action' => 'state',
                        'state'  => array(
                            'q' => 'w',
                            'x' => 'z',
                        ),
                    ),
                    'string' => 'state:?q=w&x=z',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'modify',
                        'state'     => array(
                            'q' => 'w',
                            'x' => 'z',
                        ),
                    ),
                    'string' => 'state:modify?q=w&x=z',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'modify',
                        'state'     => array(
                            'q' => 'w',
                            'x' => 'z',
                        ),
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'modify',
                        'data-ca-state'     => 'q=w&amp;x=z',
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action'    => 'state',
                        'operation' => 'reset',
                    ),
                    'string' => 'state:#!',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'operation' => 'reset',
                        'args'      => array(),
                        'state'     => array(),
                    ),
                    'string' => 'state:reset',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'reset',
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'reset',
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action'    => 'state',
                        'operation' => 'modify',
                        'state'     => array(
                            'empty' => array(),
                        )
                    ),
                    'string' => 'state:?empty=[]',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'operation' => 'modify',
                        'args'      => array(),
                        'state'     => array(
                            'empty' => array(),
                        ),
                    ),
                    'string' => 'state:modify?empty=[]',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'modify',
                        'state'     => array(
                            'empty' => array(),
                        ),
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'modify',
                        'data-ca-state'     => 'empty=[]',
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => '#myTarget',
                        'operation' => 'set',
                        'args'      => array(
                            'a' => 'b',
                            'c' => 'd',
                        ),
                        'state'     => array(
                            'q' => 'w',
                            'e' => 'r',
                        ),
                    ),
                    'string' => 'state:[#myTarget]?a=b&c=d#=q=w&e=r',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'set',
                        'state'     => array(
                            'q' => 'w',
                            'e' => 'r',
                        ),
                    ),
                    'string' => 'state:set?q=w&e=r',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'set',
                        'state'     => array(
                            'q' => 'w',
                            'e' => 'r',
                        ),
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'set',
                        'data-ca-state'     => 'q=w&amp;e=r',
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action' => 'state',
                        'args'   => array(
                            'n'  => null,
                            't'  => true,
                            'f'  => false,
                            's'  => ' str ',
                            'ip' => 123,
                            'in' => -234,
                            'fp' => 12.34,
                            'fn' => -23.45,
                        ),
                    ),
                    'string' => 'state:?n=null&t=true&f=false&s=+str%20&ip=123&in=-234&fp=12.34&fn=-23.45',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'modify',
                        'state'     => array(
                            'n'  => null,
                            't'  => true,
                            'f'  => false,
                            's'  => ' str ',
                            'ip' => 123,
                            'in' => -234,
                            'fp' => 12.34,
                            'fn' => -23.45,
                        ),
                    ),
                    'string' => 'state:modify?n=null&t=true&f=false&s=+str+&ip=123&in=-234&fp=12.34&fn=-23.45',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'modify',
                        'state'     => array(
                            'n'  => null,
                            't'  => true,
                            'f'  => false,
                            's'  => ' str ',
                            'ip' => 123,
                            'in' => -234,
                            'fp' => 12.34,
                            'fn' => -23.45,
                        ),
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'modify',
                        'data-ca-state'     => 'n=null&amp;t=true&amp;f=false&amp;s=+str+&amp;ip=123&amp;in=-234&amp;fp=12.34&amp;fn=-23.45',
                    ),
                ),
            ),

            array(
                'source'   => array(
                    'array'  => array(
                        'action' => 'state',
                        'state'  => array(
                            'a' => array(1, 2, 3),
                            'b' => array(1, 'abc', null),
                            'c' => array('x' => 'xxx', 'y' => 'yyy', 'z' => 'zzz'),
                            'x' => array('y' => array('z' => 'xyz'), 'q' => array('w' => 'e'), 't' => array(1, 2)),
                        ),
                    ),
                    'string' => 'state:?a[]=1&a[]=%32&%61[]=3&b=[1,abc,null]&c[x]=xxx&c[%79]=y%79y&c.z=%7a%7A%7a&x.y.z=xyz&x[q][w]=e&x[t][]=1&x[t][]=2',
                ),
                'expected' => array(
                    'valid'  => true,
                    'array'  => array(
                        'action'    => 'state',
                        'target'    => null,
                        'args'      => array(),
                        'operation' => 'modify',
                        'state'     => array(
                            'a' => array(1, 2, 3),
                            'b' => array(1, 'abc', null),
                            'c' => array('x' => 'xxx', 'y' => 'yyy', 'z' => 'zzz'),
                            'x' => array('y' => array('z' => 'xyz'), 'q' => array('w' => 'e'), 't' => array(1, 2)),
                        ),
                    ),
                    'string' => 'state:modify?a=[1,2,3]&b=[1,abc,null]&c.x=xxx&c.y=yyy&c.z=zzz&x.y.z=xyz&x.q.w=e&x.t=[1,2]',
                    'client' => array(
                        'action'    => 'state',
                        'operation' => 'modify',
                        'state'     => array(
                            'a'     => array(1, 2, 3),
                            'b'     => array(1, 'abc', null),
                            'c.x'   => 'xxx',
                            'c.y'   => 'yyy',
                            'c.z'   => 'zzz',
                            'x.y.z' => 'xyz',
                            'x.q.w' => 'e',
                            'x.t'   => array(1, 2),
                        ),
                    ),
                    'attrs'  => array(
                        'data-ca-action'    => 'state',
                        'data-ca-operation' => 'modify',
                        'data-ca-state'     => 'a=[1,2,3]&amp;b=[1,abc,null]&amp;c.x=xxx&amp;c.y=yyy&amp;c.z=zzz&amp;x.y.z=xyz&amp;x.q.w=e&amp;x.t=[1,2]',
                    ),
                ),
            ),
        );
    }

    /**
     * {@inheritdoc}
     * @return StateClientAction
     */
    protected function getTestClientAction($ca = null, array $config = null)
    {
        return new StateClientAction($ca, $config);
    }
}
