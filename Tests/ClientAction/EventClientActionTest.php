<?php

namespace Flying\Bundle\ClientActionBundle\Tests\ClientAction;

use Flying\Bundle\ClientActionBundle\ClientAction\EventClientAction;

class EventClientActionTest extends ClientActionTest
{
    /**
     * Test sources
     *
     * @var array
     */
    protected $tests = array(
        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event'
                ),
                'string' => 'event',
            ),
            'expected' => array(
                'valid' => false,
                'array' => array(
                    'action'    => 'event',
                    'target'    => null,
                    'args'      => array(),
                    'operation' => 'modify',
                    'state'     => array(),
                    'event'     => null,
                ),
            ),
        ),


        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'testEvent',
                ),
                'string' => 'event:testEvent',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action'    => 'event',
                    'target'    => null,
                    'args'      => array(),
                    'operation' => 'modify',
                    'state'     => array(),
                    'event'     => 'testEvent',
                ),
                'string' => 'event:testEvent',
                'client' => array(
                    'action' => 'event',
                    'event'  => 'testEvent',
                ),
                'attrs'  => array(
                    'data-ca-action' => 'event',
                    'data-ca-event'  => 'testEvent',
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action'    => 'event',
                    'event'     => 'myEvent',
                    'target'    => '#myTarget',
                    'args'      => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'operation' => 'toggle',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'string' => 'event:[#myTarget]myEvent?a=b&c=d#~q=w&e=r',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action'    => 'event',
                    'event'     => 'myEvent',
                    'target'    => '#myTarget',
                    'args'      => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'operation' => 'toggle',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'string' => 'event:[#myTarget]myEvent?a=b&c=d#~q=w&e=r',
                'client' => array(
                    'action'    => 'event',
                    'event'     => 'myEvent',
                    'target'    => '#myTarget',
                    'args'      => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'operation' => 'toggle',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'attrs'  => array(
                    'data-ca-action'    => 'event',
                    'data-ca-event'     => 'myEvent',
                    'data-ca-target'    => '#myTarget',
                    'data-ca-args'      => 'a=b&amp;c=d',
                    'data-ca-operation' => 'toggle',
                    'data-ca-state'     => 'q=w&amp;e=r',
                ),
            ),
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function dpGetModified()
    {
        // @TODO Implement data provider for client action modifications
        $ca = 'event:[#myTarget]someEvent?a=b&c=d&t=[1,2,3]#q=w&e=r&t=[1,2,3]';
        return array(
            array($ca, array()),
        );
    }

    /**
     * {@inheritdoc}
     * @return EventClientAction
     */
    protected function getTestClientAction($ca = null, array $config = null)
    {
        return new EventClientAction($ca, $config);
    }
}
