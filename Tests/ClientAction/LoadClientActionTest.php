<?php

namespace Flying\Bundle\ClientActionBundle\Tests\ClientAction;

use Flying\Bundle\ClientActionBundle\ClientAction\LoadClientAction;
use Mockery;

class LoadClientActionTest extends ClientActionTest
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
                    'action' => 'load'
                ),
                'string' => 'load',
            ),
            'expected' => array(
                'valid' => false,
                'array' => array(
                    'action'    => 'load',
                    'target'    => null,
                    'args'      => array(),
                    'operation' => 'modify',
                    'state'     => array(),
                    'url'       => null,
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                ),
                'string' => 'load:/some/url',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action'    => 'load',
                    'target'    => null,
                    'args'      => array(),
                    'operation' => 'modify',
                    'state'     => array(),
                    'url'       => '/some/url',
                ),
                'string' => 'load:/some/url',
                'client' => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                ),
                'attrs'  => array(
                    'data-ca-action' => 'load',
                    'data-ca-url'    => '/some/url',
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action'    => 'load',
                    'url'       => '/some/url',
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
                'string' => 'load:[#myTarget]/some/url?a=b&c=d#~q=w&e=r',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action'    => 'load',
                    'url'       => '/some/url',
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
                'string' => 'load:[#myTarget]/some/url?a=b&c=d#~q=w&e=r',
                'client' => array(
                    'action'    => 'load',
                    'url'       => '/some/url',
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
                    'data-ca-action'    => 'load',
                    'data-ca-url'       => '/some/url',
                    'data-ca-target'    => '#myTarget',
                    'data-ca-args'      => 'a=b&amp;c=d',
                    'data-ca-operation' => 'toggle',
                    'data-ca-state'     => 'q=w&amp;e=r',
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action'    => 'load',
                    'url'       => 'my_route_name',
                    'target'    => '#myTarget',
                    'args'      => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'operation' => 'set',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'string' => 'load:[#myTarget]my_route_name?a=b&c=d#=q=w&e=r',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action'    => 'load',
                    'url'       => 'my_route_name',
                    'target'    => '#myTarget',
                    'args'      => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'operation' => 'set',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'string' => 'load:[#myTarget]my_route_name?a=b&c=d#=q=w&e=r',
                'client' => array(
                    'action'    => 'load',
                    'url'       => '/generated/url',
                    'target'    => '#myTarget',
                    'operation' => 'set',
                    'state'     => array(
                        'q' => 'w',
                        'e' => 'r',
                    ),
                ),
                'attrs'  => array(
                    'data-ca-action'    => 'load',
                    'data-ca-url'       => '/generated/url',
                    'data-ca-target'    => '#myTarget',
                    'data-ca-operation' => 'set',
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
        $ca = 'load:[#myTarget]/some/url?a=b&c=d&t=[1,2,3]#q=w&e=r&t=[1,2,3]';
        return array(
            array($ca, array()),
        );
    }

    /**
     * {@inheritdoc}
     * @return LoadClientAction
     */
    protected function getTestClientAction($ca = null, array $config = null)
    {
        if (!is_array($config)) {
            $config = array();
        }
        if (!array_key_exists('url_generator', $config)) {
            $generator = Mockery::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface')
                ->shouldReceive('generate')->zeroOrMoreTimes()->andReturn('/generated/url')->getMock();
            $config['url_generator'] = $generator;
        }
        $ca = new LoadClientAction($ca, $config);
        return $ca;
    }


    /**
     * @param LoadClientAction $ca
     * @dataProvider dpUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes
     */
    public function testUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes(LoadClientAction $ca)
    {
        $ca->setConfig('url_generator', null);
        $ca->isValid();
        $ca->toArray();
        $ca->toString();
        if (($ca->action == 'load') && (strpos($ca->url, '/') === false)) {
            $this->setExpectedException('\RuntimeException');
        }
        $ca->toClient();
    }

    public function dpUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes()
    {
        $tests = array();
        foreach ($this->tests as $test) {
            if ((array_key_exists('array', $test['source'])) && (array_key_exists('valid', $test['expected']))) {
                if (!$test['expected']['valid']) {
                    continue;
                }
                $ca = $this->getTestClientAction($test['source']['array']);
                $tests[] = array($ca);
            }
        }
        return $tests;
    }
}
