<?php

namespace Flying\Bundle\ClientActionBundle\ClientAction;

use Flying\Struct\Common\ComplexPropertyInterface;
use Flying\Struct\Configuration;
use Flying\Struct\Property\Collection;
use Flying\Struct\Struct;

/**
 * Client action information structure
 *
 * @property string $action         Client action to perform
 * @property string $target         CSS selector for action target
 * @property Collection $args       Additional arguments for client action
 *
 * @Struct\String(name="action", nullable=false)
 * @Struct\String(name="target", nullable=true)
 * @Struct\Collection(name="args")
 */
abstract class ClientAction extends Struct
{
    /**
     * Class constructor
     *
     * @param string|array|ClientAction $ca OPTIONAL Client action information
     * @param array|object $config          OPTIONAL Configuration options
     * @return ClientAction
     */
    public function __construct($ca = null, $config = null)
    {
        parent::__construct(null, $config);
        if ($ca) {
            $ca = $this->parse($ca);
            $this->set($ca);
        }
    }

    /**
     * Convert client action information to its string representation
     *
     * @throws \RuntimeException
     * @return string
     */
    public function toString()
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Only valid client actions can be rendered as strings');
        }
        $parts = $this->toArray();
        $result = $parts['action'] . ':';
        if ($parts['target']) {
            $result .= '[' . $parts['target'] . ']';
        }
        $result .= $this->actionToString();
        if (sizeof($parts['args'])) {
            $result .= '?' . $this->buildQueryString($parts['args']);
        }
        return $result;
    }

    /**
     * Convert client action contents to string representation
     *
     * @return string
     */
    abstract protected function actionToString();

    /**
     * Get client action information suitable to pass to client side of application
     *
     * @throws \RuntimeException
     * @return array
     */
    public function toClient()
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Only valid client actions can be rendered to their client representation');
        }
        $parts = $this->toArray();
        $client = array_filter($parts, function ($v) {
            if (($v === null) || (is_array($v)) && (!sizeof($v))) {
                return false;
            }
            return true;
        });
        if (array_key_exists('args', $client)) {
            $client['args'] = $this->toPlainArray($client['args']);
        }
        return $client;
    }

    /**
     * Get client action information as set of HTML attributes
     *
     * @throws \RuntimeException
     * @return array
     */
    public function toAttrs()
    {
        $attrs = array();
        $client = $this->toClient();
        foreach ($client as $name => $value) {
            $name = 'data-ca-' . $name;
            if (is_array($value)) {
                $value = $this->buildQueryString($value);
            }
            $attrs[$name] = htmlspecialchars($value);
        }
        return $attrs;
    }

    /**
     * Check if client action is valid
     *
     * @return boolean
     */
    abstract public function isValid();

    /**
     * Get copy of this client action object with given modifications applied
     *
     * @param array|string|ClientAction $modifications Modifications for client action to apply
     * @return ClientAction
     */
    public function getModified($modifications)
    {
        $modifications = array_filter($this->parse($modifications), function ($v) {
            return (is_array($v)) ? (sizeof($v) > 0) : ($v !== null);
        });
        $ca = clone $this;
        foreach ($modifications as $name => $value) {
            $orig = $this->get($name);
            if ($orig instanceof ComplexPropertyInterface) {
                if (is_array($value)) {
                    $value = array_replace($orig->toArray(), $value);
                } else {
                    continue;
                }
            }
            $ca->set($name, $value);
        }
        return $ca;
    }

    /**
     * Parse given client action information
     *
     * @param string|array|ClientAction $action Client action information to parse
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function parse($action)
    {
        $parts = array();
        $keys = array_keys($this->struct);
        foreach ($keys as $key) {
            $parts[$key] = null;
        }
        $parts = $this->preParse($action, $parts);
        if (is_string($action)) {
            if (strpos($action, ':') !== false) {
                $t = explode(':', $action, 2);
                $parts['action'] = array_shift($t);
                $action = array_shift($t);
                if (preg_match('/^(?:\[([^\]]*)\])(.*)/', $action, $t)) {
                    if (strlen($t[1])) {
                        $parts['target'] = $t[1];
                    }
                    $action = $t[2];
                }
                if (strpos($action, '?') !== false) {
                    $t = explode('?', $action);
                    $args = array_pop($t);
                    $action = join('?', $t);
                    $parts['args'] = $this->parseQueryString($args);
                }
                if (strlen($action)) {
                    $parts['contents'] = $action;
                }
            } else {
                $parts['action'] = $action;
            }
        } elseif ($action instanceof ClientAction) {
            $parts = $action->toArray();
        } elseif (is_array($action)) {
            foreach ($action as $name => $value) {
                if (!array_key_exists($name, $parts)) {
                    continue;
                }
                if (is_array($value)) {
                    $parts[$name] = $this->fromPlainArray($value);
                } else {
                    $parts[$name] = $this->convertValueToNative($value);
                }
            }
        } else {
            throw new \InvalidArgumentException('Given client action information is not recognized');
        }
        $parts = $this->postParse($parts);
        return ($parts);
    }

    /**
     * Perform pre-parsing of given client action information into given parts structure
     *
     * @param string|array|ClientAction $action Client action information to parse
     * @param array $parts
     * @return array
     */
    protected function preParse(&$action, $parts)
    {
        return $parts;
    }

    /**
     * Perform pre-parsing of given client action parts information structure
     *
     * @param array $parts
     * @return array
     */
    protected function postParse($parts)
    {
        if ((array_key_exists('args', $parts)) && (!is_array($parts['args']))) {
            $parts['args'] = array();
        }
        return $parts;
    }

    /**
     * Parse given query string and return list of arguments
     * parse_str() is not used because it converts "." into "_"
     * and doesn't support advanced formatting
     *
     * @param string $string
     * @return array
     */
    protected function parseQueryString($string)
    {
        $args = array();
        if (!strlen($string)) {
            return $args;
        }
        $parts = explode('&', $string);
        foreach ($parts as $part) {
            $part = explode('=', $part, 2);
            $name = array_shift($part);
            $value = array_shift($part);
            $indexes = array();
            if (strpos($name, '[') !== false) {
                $temp = explode('[', $name, 2);
                $name = array_shift($temp);
                $temp = array_shift($temp);
                $temp = preg_replace('/\]$/', '', $temp);
                $indexes = explode('][', $temp);
            } elseif (strpos($name, '.') !== false) {
                $indexes = explode('.', $name);
                $name = array_shift($indexes);
            }
            $name = $this->convertValueToNative(urldecode($name));
            if ($value === '[]') {
                $value = array();
            } elseif (preg_match('/^\[(.*?)\]$/', $value, $t)) {
                $t = explode(',', $t[1]);
                $value = array();
                foreach ($t as $v) {
                    $value[] = $this->convertValueToNative(urldecode($v));
                }
            } else {
                $value = $this->convertValueToNative(urldecode($value));
            }
            if (sizeof($indexes)) {
                $arg = ((array_key_exists($name, $args)) && (is_array($args[$name]))) ? $args[$name] : array();
                $a = & $arg;
                do {
                    $i = array_shift($indexes);
                    $i = $this->convertValueToNative(urldecode($i));
                    if (sizeof($indexes)) {
                        if ((!array_key_exists($i, $a)) || (!is_array($a[$i]))) {
                            $a[$i] = array();
                        }
                        $a = & $a[$i];
                    } else {
                        if ($i !== '') {
                            $a[$i] = $value;
                        } else {
                            $a[] = $value;
                        }
                    }
                } while (sizeof($indexes));
                $args[$name] = $arg;
            } else {
                $args[$name] = $value;
            }
        }
        return $args;
    }

    /**
     * Build query string from given list of arguments
     * http_build_query() is not used because of lack of advanced functionality
     *
     * @param array $args
     * @return string
     */
    protected function buildQueryString(array $args)
    {
        $query = array();
        $args = $this->toPlainArray($args);
        foreach ($args as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = urlencode($this->convertNativeToString($v));
                }
                $value = '[' . join(',', $value) . ']';
            } else {
                $value = urlencode($this->convertNativeToString($value));
            }
            $query[] = urlencode($name) . '=' . $value;
        }
        return join('&', $query);
    }

    /**
     * Convert given array from plain array with dotted keys notation into normal array
     *
     * @param array $array
     * @return array
     */
    protected function fromPlainArray(array $array)
    {
        $result = array();
        foreach ($array as $name => $value) {
            $target = & $result;
            $parts = explode('.', $name);
            do {
                $part = array_shift($parts);
                if ((!array_key_exists($part, $target)) || (!is_array($target[$part]))) {
                    $target[$part] = array();
                }
                if (sizeof($parts)) {
                    $target = & $target[$part];
                } else {
                    if (is_array($value)) {
                        array_walk_recursive($value, array($this, 'convertValueToNativeRef'));
                    } else {
                        $value = $this->convertValueToNative($value);
                    }
                    $target[$part] = $value;
                }
            } while (sizeof($parts));
        }
        return $result;
    }

    /**
     * Convert given array into plain array
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    protected function toPlainArray(array $array, $prefix = '')
    {
        if ((strlen($prefix)) && (substr($prefix, -1) !== '.')) {
            $prefix .= '.';
        }
        $plain = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $range = range(0, sizeof($value) - 1);
                if ($range !== array_keys($value)) {
                    $value = $this->toPlainArray($value, $prefix . $key);
                    if (sizeof($value)) {
                        $plain = array_replace($plain, $value);
                        continue;
                    }
                }
            }
            $plain[$prefix . $key] = $value;
        }
        return $plain;
    }

    /**
     * Convert given value into native type
     *
     * @param mixed $value
     * @return mixed
     */
    protected function convertValueToNative($value)
    {
        if ($value === 'null') {
            $value = null;
        } elseif ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        } elseif (preg_match('/^\-?\d+$/', $value)) {
            $value = (int)$value;
        } elseif ($temp = filter_var($value, FILTER_VALIDATE_INT)) {
            $value = $temp;
        } elseif ($temp = filter_var($value, FILTER_VALIDATE_FLOAT)) {
            $value = $temp;
        }
        return $value;
    }

    /**
     * Alias of convertValueToNative() but with $value passed by reference
     * Required because $this can't be passed to closures in PHP 5.3.x
     *
     * @param mixed $value
     * @return void
     */
    protected function convertValueToNativeRef(&$value)
    {
        $value = $this->convertValueToNative($value);
    }

    /**
     * Convert given native value into string
     *
     * @param mixed $value
     * @return mixed
     */
    protected function convertNativeToString($value)
    {
        if ($value === null) {
            return 'null';
        } elseif ($value === true) {
            return 'true';
        } elseif ($value === false) {
            return 'false';
        } else {
            return (string)$value;
        }
    }
}
