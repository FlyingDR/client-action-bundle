<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Struct;

use Flying\Bundle\ClientActionBundle\Struct\ClientAction;
use Flying\Struct\Common\ComplexPropertyInterface;
use Flying\Tests\TestCase;
use Mockery;

class ClientActionTest extends TestCase
{
    protected $tests = array(
        array(
            'source'   => array(
                'array'  => array(),
                'string' => '',
            ),
            'expected' => array(
                'valid' => false,
                'array' => array(
                    'action' => 'event',
                    'target' => null,
                    'event'  => null,
                    'url'    => null,
                    'args'   => array(),
                    'state'  => array(),
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event'
                ),
                'string' => 'event',
            ),
            'expected' => array(
                'valid' => false,
                'array' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load'
                ),
                'string' => 'load',
            ),
            'expected' => array(
                'valid' => false,
                'array' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'state'
                ),
                'string' => 'state',
            ),
            'expected' => array(
                'valid' => false,
                'array' => true,
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
                    'action' => 'event',
                    'target' => null,
                    'event'  => null,
                    'url'    => null,
                    'args'   => array(),
                    'state'  => array(),
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'myEvent',
                ),
                'string' => 'event:myEvent',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'myEvent',
                    'target' => '#myTarget',
                ),
                'string' => 'event:[#myTarget]myEvent',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'myEvent',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                ),
                'string' => 'event:[#myTarget]myEvent?a=b&c=d',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'myEvent',
                    'target' => '#myTarget',
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
                ),
                'string' => 'event:[#myTarget]myEvent#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'event',
                    'event'  => 'myEvent',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
                ),
                'string' => 'event:[#myTarget]myEvent?a=b&c=d#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
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
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                    'target' => '#myTarget',
                ),
                'string' => 'load:[#myTarget]/some/url',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                ),
                'string' => 'load:[#myTarget]/some/url?a=b&c=d',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
                ),
                'string' => 'load:[#myTarget]/some/url?a=b&c=d#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => '/some/url',
                    'target' => '#myTarget',
                    'args'   => 'abc',
                    'state'  => 'xyz',
                ),
                'string' => 'load:[#myTarget]/some/url',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => array(
                    'action' => 'load',
                    'target' => '#myTarget',
                    'event'  => null,
                    'url'    => '/some/url',
                    'args'   => array(),
                    'state'  => array(),
                ),
                'string' => true,
                'client' => array(
                    'action' => 'load',
                    'target' => '#myTarget',
                    'url'    => '/some/url',
                ),
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'load',
                    'url'    => 'my_route_name',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
                ),
                'string' => 'load:[#myTarget]my_route_name?a=b&c=d#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => array(
                    'action' => 'load',
                    'target' => '#myTarget',
                    'url'    => '/generated/url',
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
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
                'string' => 'state:#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),

        array(
            'source'   => array(
                'array'  => array(
                    'action' => 'state',
                    'target' => '#myTarget',
                    'args'   => array(
                        'a' => 'b',
                        'c' => 'd',
                    ),
                    'state'  => array(
                        'q' => 'w',
                        'x' => 'z',
                    ),
                ),
                'string' => 'state:[#myTarget]?a=b&c=d#q=w&x=z',
            ),
            'expected' => array(
                'valid'  => true,
                'array'  => true,
                'string' => true,
                'client' => true,
            ),
        ),
    );

    /**
     * Prepare and get test data set
     *
     * @return array
     */
    protected function getTests()
    {
        $tests = array();
        $ca = new ClientAction();
        $ca = $ca->toArray();
        foreach ($this->tests as $test) {
            $expected = $test['expected'];
            foreach ($expected as $key => $value) {
                if ($key == 'valid') {
                    continue;
                }
                if ($value === true) {
                    if (array_key_exists($key, $test['source'])) {
                        $value = $test['source'][$key];
                    } elseif (array_key_exists('array', $test['source'])) {
                        $value = $test['source']['array'];
                    }
                    // Normalize value to make sure that it have full set of keys from client action structure
                    if (is_array($value)) {
                        $temp = $ca;
                        foreach (array_keys($temp) as $nk) {
                            if (array_key_exists($nk, $value)) {
                                $temp[$nk] = $value[$nk];
                            }
                        }
                        $value = $temp;
                    }
                    if ($key == 'client') {
                        $value = $test['source']['array'];
                        if (array_key_exists('action', $value)) {
                            // Filter out client action parameters that are not exported to client side
                            // @see Flying\Bundle\ClientActionBundle\Struct\ClientAction::toClient()
                            switch ($value['action']) {
                                case 'load':
                                    unset($value['event']);
                                    break;
                                case 'event':
                                    unset($value['url']);
                                    break;
                                case 'state':
                                    unset($value['target']);
                                    unset($value['event']);
                                    unset($value['url']);
                                    unset($value['args']);
                                    break;
                            }
                        }
                    }
                }
                $test['expected'][$key] = $value;
            }
            $tests[] = $test;
        }
        return $tests;
    }

    /**
     * Get test client action object
     *
     * @param mixed $ca
     * @param array $config
     * @return ClientAction
     */
    protected function getTestClientAction($ca, array $config = null)
    {
        if (!is_array($config)) {
            $config = array();
        }
        if (!array_key_exists('url_generator', $config)) {
            $generator = Mockery::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface')
                ->shouldReceive('generate')->zeroOrMoreTimes()->andReturn('/generated/url')->getMock();
            $config['url_generator'] = $generator;
        }
        $ca = new ClientAction($ca, $config);
        return $ca;
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider dataProviderCreationFromArray
     */
    public function testCreationFromArray(array $data, array $expected = null)
    {
        $ca = $this->getTestClientAction($data);
        if (!is_array($expected)) {
            $expected = $data;
        }
        foreach ($ca as $name => $value) {
            if (array_key_exists($name, $expected)) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                $this->assertEquals($expected[$name], $value);
            }
        }
    }

    public function dataProviderCreationFromArray()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $tests[] = array($test['source']['array'], $test['expected']['array']);
        }
        return $tests;
    }

    /**
     * @param string $ca
     * @param array $expected
     * @dataProvider dataProviderCreationFromString
     */
    public function testCreationFromString($ca, array $expected)
    {
        $ca = $this->getTestClientAction($ca);
        foreach ($ca as $name => $value) {
            if (array_key_exists($name, $expected)) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                $this->assertEquals($expected[$name], $value);
            }
        }
    }

    public function dataProviderCreationFromString()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $tests[] = array($test['source']['string'], $test['expected']['array']);
        }
        return $tests;
    }


    /**
     * @param ClientAction $data
     * @param array $expected
     * @dataProvider dataProviderCreationFromClientAction
     */
    public function testCreationFromClientAction(ClientAction $data, array $expected = null)
    {
        $ca = $this->getTestClientAction($data);
        if (!is_array($expected)) {
            $expected = $data;
        }
        foreach ($ca as $name => $value) {
            if (array_key_exists($name, $expected)) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                $this->assertEquals($expected[$name], $value);
            }
        }
    }

    public function dataProviderCreationFromClientAction()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $ca = $this->getTestClientAction($test['source']['array']);
            $tests[] = array($ca, $test['expected']['array']);
        }
        return $tests;
    }

    /**
     * @param string $ca
     * @param boolean $expected
     * @dataProvider dataProviderIsValid
     */
    public function testIsValid($ca, $expected)
    {
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->isValid();
        $this->assertEquals($expected, $actual);
    }

    public function dataProviderIsValid()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $tests[] = array($test['source']['string'], $test['expected']['valid']);
        }
        return $tests;
    }

    /**
     * @param string $ca
     * @param string $expected
     * @param boolean $valid
     * @dataProvider dataProviderConversionToString
     */
    public function testConversionToString($ca, $expected, $valid)
    {
        if (!$valid) {
            $this->setExpectedException('\RuntimeException');
        }
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->toString();
        if ($valid) {
            $this->assertEquals($expected, $actual);
        }
    }

    public function dataProviderConversionToString()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $expected = ($test['expected']['valid']) ? $test['expected']['string'] : null;
            $tests[] = array($test['source']['string'], $expected, $test['expected']['valid']);
        }
        return $tests;
    }

    /**
     * @param string $ca
     * @param array $expected
     * @dataProvider dataProviderConversionToArray
     */
    public function testConversionToArray($ca, array $expected)
    {
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function dataProviderConversionToArray()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $tests[] = array($test['source']['string'], $test['expected']['array']);
        }
        return $tests;
    }

    /**
     * @param string $ca
     * @param array $expected
     * @param boolean $exception
     * @dataProvider dataProviderConversionToClient
     */
    public function testConversionToClient($ca, array $expected, $exception = false)
    {
        $ca = $this->getTestClientAction($ca);
        if ($exception) {
            $this->setExpectedException('\RuntimeException');
        }
        $actual = $ca->toClient();
        if (!$exception) {
            $this->assertEquals($expected, $actual);
        }
    }

    public function dataProviderConversionToClient()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            $item = array($test['source']['string']);
            if ($test['expected']['valid']) {
                $item[] = $test['expected']['client'];
                $item[] = false;
            } else {
                $item[] = array();
                $item[] = true;
            }
            $tests[] = $item;
        }
        return $tests;
    }

    public function testOnlyValidClientActionsCanBeConvertedToClient()
    {
        $ca = $this->getTestClientAction(array());
        $this->assertFalse($ca->isValid());
        $this->setExpectedException('\RuntimeException');
        $ca->toClient();
    }

    /**
     * @param ClientAction $ca
     * @dataProvider dataProviderUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes
     */
    public function testUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes(ClientAction $ca)
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

    public function dataProviderUrlGeneratorIsRequiredOnlyForClientActionsWithRoutes()
    {
        $tests = array();
        foreach ($this->getTests() as $test) {
            if (!$test['expected']['valid']) {
                continue;
            }
            $ca = $this->getTestClientAction($test['source']['array']);
            $tests[] = array($ca);
        }
        return $tests;
    }

    /**
     * @param array $modification
     * @dataProvider dataProviderGetModified
     */
    public function testGetModified(array $modification)
    {
        $ca = $this->getTestClientAction('load:[#myTarget]/some/url?a=b&c=d#q=w&x=z');
        $modified = $ca->getModified($modification);
        $this->assertFalse($ca === $modified);
        $expected = $ca->toArray();
        foreach ($modification as $name => $value) {
            if (!array_key_exists($name, $expected)) {
                continue;
            }
            if (is_array($expected[$name])) {
                if (is_array($value)) {
                    $value = array_replace($expected[$name], $value);
                } else {
                    $value = $expected[$name];
                }
            }
            $expected[$name] = $value;
        }
        $actual = $modified->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function dataProviderGetModified()
    {
        return array(
            array(array()),
            array(
                array(
                    'action' => 'state',
                ),
            ),
            array(
                array(
                    'target' => null,
                ),
            ),
            array(
                array(
                    'action' => 'event',
                    'target' => '.someClass',
                    'event'  => 'someEvent',
                    'url'    => null,
                ),
            ),
            array(
                array(
                    'args'  => null,
                    'state' => 'some string value',
                ),
            ),
            array(
                array(
                    'url'   => '/some/other/url',
                    'args'  => array('abc' => 'def', 'test' => 12345, 'a' => 'modified'),
                    'state' => array('xyz' => 777, 'q' => 'changed'),
                ),
            ),
        );
    }

    /**
     * @param string $value
     * @param mixed $expected
     * @dataProvider dpValuesConversion
     */
    public function testValuesConversion($value, $expected)
    {
        $ca = new ClientAction('state:?arg=' . $value . '#s=' . $value);
        $this->assertSame($expected, $ca->args['arg']);
        $this->assertSame($expected, $ca->state['s']);
        $ca = new ClientAction('state:?arg[test]=' . $value . '#s[test]=' . $value);
        $a1 = $ca->args['arg'];
        $a2 = $ca->args['arg']['test'];
        $this->assertSame($expected, $ca->args['arg']['test']);
        $this->assertSame($expected, $ca->state['s']['test']);
    }

    public function dpValuesConversion()
    {
        return array(
            array('null', null),
            array('true', true),
            array('false', false),
            array('12', 12),
            array('-123', -123),
            array('0.5', 0.5),
            array('-0.15', -0.15),
            array('12.34', 12.34),
            array('-12.34', -12.34),
            array('12e3', 12000.0),
            array('12e-3', 0.012),
            array('-12.5e-3', -0.0125),
            array('1a', '1a'),
        );
    }
}
