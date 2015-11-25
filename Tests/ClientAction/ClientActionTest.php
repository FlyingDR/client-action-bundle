<?php

namespace Flying\Bundle\ClientActionBundle\Tests\ClientAction;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;
use Flying\Bundle\ClientActionBundle\Tests\TestCase;
use Flying\Struct\Common\ComplexPropertyInterface;

abstract class ClientActionTest extends TestCase
{
    /**
     * Test sources
     *
     * @var array
     */
    protected static $tests = array();

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider dpCreationFromArray
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
                static::assertEquals($expected[$name], $value);
            }
        }
    }

    public function dpCreationFromArray()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('array', $test['source'])) && (array_key_exists('array', $test['expected']))) {
                $tests[] = array($test['source']['array'], $test['expected']['array']);
            }
        }
        return $tests;
    }

    /**
     * @param ClientAction|string $ca
     * @param array $expected
     * @dataProvider dpCreationFromString
     */
    public function testCreationFromString($ca, array $expected)
    {
        $ca = $this->getTestClientAction($ca);
        foreach ($ca as $name => $value) {
            if (array_key_exists($name, $expected)) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                static::assertEquals($expected[$name], $value);
            }
        }
    }

    public function dpCreationFromString()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) && (array_key_exists('array', $test['expected']))) {
                $tests[] = array($test['source']['string'], $test['expected']['array']);
            }
        }
        return $tests;
    }

    /**
     * @param ClientAction $data
     * @param array $expected
     * @dataProvider dpCreationFromClientAction
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
                static::assertEquals($expected[$name], $value);
            }
        }
    }

    public function dpCreationFromClientAction()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('array', $test['source'])) && (array_key_exists('array', $test['expected']))) {
                $ca = $this->getTestClientAction($test['source']['array']);
                $tests[] = array($ca, $test['expected']['array']);
            }
        }
        return $tests;
    }

    public function testClientActionTypeIsNotMutable()
    {
        $ca = $this->getTestClientAction();
        $action = $ca->action;
        $ca->action = 'modified';
        static::assertEquals($ca->action, $action);
    }

    /**
     * @param ClientAction|string $ca
     * @param boolean $expected
     * @dataProvider dpCsValid
     */
    public function testIsValid($ca, $expected)
    {
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->isValid();
        static::assertEquals($expected, $actual);
    }

    public function dpCsValid()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) && (array_key_exists('valid', $test['expected']))) {
                $tests[] = array($test['source']['string'], $test['expected']['valid']);
            }
        }
        return $tests;
    }

    /**
     * @param ClientAction|string $ca
     * @param string $expected
     * @param boolean $valid
     * @dataProvider dpConversionToString
     */
    public function testConversionToString($ca, $expected, $valid)
    {
        if (!$valid) {
            $this->setExpectedException('\RuntimeException');
        }
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->toString();
        if ($valid) {
            static::assertEquals($expected, $actual);
        }
    }

    public function dpConversionToString()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) &&
                (array_key_exists('valid', $test['expected'])) &&
                (array_key_exists('string', $test['expected']))
            ) {
                $expected = ($test['expected']['valid']) ? $test['expected']['string'] : null;
                $tests[] = array($test['source']['string'], $expected, $test['expected']['valid']);
            }
        }
        return $tests;
    }

    public function testOnlyValidClientActionsCanBeConvertedToString()
    {
        $ca = $this->getTestClientAction(array());
        static::assertFalse($ca->isValid());
        $this->setExpectedException('\RuntimeException');
        $ca->toString();
    }

    /**
     * @param ClientAction|string $ca
     * @param array $expected
     * @dataProvider dpConversionToArray
     */
    public function testConversionToArray($ca, array $expected)
    {
        $ca = $this->getTestClientAction($ca);
        $actual = $ca->toArray();
        static::assertEquals($expected, $actual);
    }

    public function dpConversionToArray()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) && (array_key_exists('array', $test['expected']))) {
                $tests[] = array($test['source']['string'], $test['expected']['array']);
            }
        }
        return $tests;
    }

    /**
     * @param ClientAction|string $ca
     * @param array $expected
     * @param boolean $exception
     * @dataProvider dpConversionToClient
     */
    public function testConversionToClient($ca, array $expected, $exception = false)
    {
        $ca = $this->getTestClientAction($ca);
        if ($exception) {
            $this->setExpectedException('\RuntimeException');
        }
        $actual = $ca->toClient();
        if (!$exception) {
            static::assertEquals($expected, $actual);
        }
    }

    public function dpConversionToClient()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) &&
                (array_key_exists('valid', $test['expected'])) &&
                (array_key_exists('client', $test['expected']))
            ) {
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
        }
        return $tests;
    }

    public function testOnlyValidClientActionsCanBeConvertedToClient()
    {
        $ca = $this->getTestClientAction(array());
        static::assertFalse($ca->isValid());
        $this->setExpectedException('\RuntimeException');
        $ca->toClient();
    }

    /**
     * @param ClientAction|string $ca
     * @param array $expected
     * @param boolean $exception
     * @dataProvider dpConversionToAttrs
     */
    public function testConversionToAttrs($ca, array $expected, $exception = false)
    {
        $ca = $this->getTestClientAction($ca);
        if ($exception) {
            $this->setExpectedException('\RuntimeException');
        }
        $actual = $ca->toAttrs();
        if (!$exception) {
            static::assertEquals($expected, $actual);
        }
    }

    public function dpConversionToAttrs()
    {
        $tests = array();
        foreach ($this->getTestDataSets() as $test) {
            if ((array_key_exists('string', $test['source'])) &&
                (array_key_exists('valid', $test['expected'])) &&
                (array_key_exists('attrs', $test['expected']))
            ) {
                $item = array($test['source']['string']);
                if ($test['expected']['valid']) {
                    $item[] = $test['expected']['attrs'];
                    $item[] = false;
                } else {
                    $item[] = array();
                    $item[] = true;
                }
                $tests[] = $item;
            }
        }
        return $tests;
    }

    public function testOnlyValidClientActionsCanBeConvertedToAttrs()
    {
        $ca = $this->getTestClientAction(array());
        static::assertFalse($ca->isValid());
        $this->setExpectedException('\RuntimeException');
        $ca->toAttrs();
    }

    /**
     * @param ClientAction|string $ca
     * @param array $modification
     * @dataProvider dpGetModified
     */
    public function testGetModified($ca, array $modification)
    {
        $ca = $this->getTestClientAction($ca);
        $modified = $ca->getModified($modification);
        static::assertFalse($ca === $modified);
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
        static::assertEquals($expected, $actual);
    }

    abstract public function dpGetModified();

    /**
     * @param string $value
     * @param mixed $expected
     * @dataProvider dpValuesConversion
     */
    public function testValuesConversion($value, $expected)
    {
        $ca = $this->getTestClientAction();
        $action = $ca->action;
        $ca = $this->getTestClientAction($action . ':?arg=' . $value);
        static::assertSame($expected, $ca->args['arg']);
        $ca = $this->getTestClientAction($action . ':?arg[test]=' . $value);
        static::assertSame($expected, $ca->args['arg']['test']);
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

    /**
     * Get test data sets to run tests on
     *
     * @return array
     */
    abstract protected function getTestDataSets();

    /**
     * Get test client action object
     *
     * @param mixed $ca
     * @param array $config
     * @return ClientAction
     */
    abstract protected function getTestClientAction($ca = null, array $config = null);
}
