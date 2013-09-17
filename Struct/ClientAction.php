<?php

namespace Flying\Bundle\ClientActionBundle\Struct;

use Flying\Struct\Common\ComplexPropertyInterface;
use Flying\Struct\Configuration;
use Flying\Struct\Property\Collection;
use Flying\Struct\Struct;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Client action information structure
 *
 * @property string $action         Client action to perform
 * @property string $target         jQuery selector for action target
 * @property string $event          Event to trigger as action
 * @property string $url            URL or route name to load from server as action
 * @property Collection $args       Additional arguments to pass along with event or URL
 * @property Collection $state      Changes to application state object to apply
 *
 * @Struct\Enum(name="action", values={"load","event","state"}, default="event", nullable=false)
 * @Struct\String(name="target", nullable=true)
 * @Struct\String(name="event", nullable=true)
 * @Struct\String(name="url", nullable=true)
 * @Struct\Collection(name="args")
 * @Struct\Collection(name="state")
 *
 * Client action can be defined as URI-like form:
 *
 * [action ":"] ["[" target "]"]? [url | route]? ["?" args]? ["#" state]?
 *
 * See examples about details of format:
 *
 * Set application state object properties:
 * state:?param1=value1&param2=value2
 * state:#param1=value1&param2=value2
 *
 * Trigger event "eventName":
 * event:eventName
 *
 * Trigger event "eventName" with specified additional arguments:
 * event:eventName?arg1=value1&arg2=value2
 *
 * Trigger event "eventName" with additional arguments and applied modifications to application state:
 * event:eventName?arg1=value1&arg2=value2#param1=value1&param2=value2
 *
 * Trigger event "eventName" to "#targetId" page element and additional arguments:
 * event:[#targetId]eventName?arg1=value1&arg2=value2
 *
 * Load information from server from "/some/url/path" "#targetId" page element
 *      with additional arguments and application state modifications:
 * load:[#targetId]/some/url/path?arg1=value1&arg2=value2#param1=value1&param2=value2
 *
 * Load information from URL generated for "my_route" route with state modifications into #targetId target:
 * load:[#targetId]my_route#param1=value1&param2=value2
 */
class ClientAction extends Struct
{
    /**
     * Class constructor
     *
     * @param string|array|ClientAction $ca    OPTIONAL Client action information
     * @param array|object $config             OPTIONAL Configuration options
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
        if ($parts['action'] == 'event') {
            $result .= $parts['event'];
        } else {
            $result .= $parts['url'];
        }
        if (sizeof($parts['args'])) {
            $result .= '?' . http_build_query($parts['args']);
        }
        if (sizeof($parts['state'])) {
            $result .= '#' . http_build_query($parts['state']);
        }
        return $result;
    }

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
        switch ($parts['action']) {
            case 'load':
                unset($parts['event']);
                if (strpos($parts['url'], '/') === false) {
                    // Render route name into URL
                    /** @var $generator UrlGeneratorInterface */
                    $generator = $this->getConfig('url_generator');
                    if (!$generator) {
                        throw new \RuntimeException('URL generator service should be provided to allow handling routes in client actions');
                    }
                    $parts['url'] = $generator->generate($parts['url'], $parts['args']);
                    unset($parts['args']);
                }
                break;
            case 'event':
                unset($parts['url']);
                break;
            case 'state':
                unset($parts['target']);
                unset($parts['event']);
                unset($parts['url']);
                unset($parts['args']);
                if (!sizeof($parts['state'])) {
                    throw new \RuntimeException('Client action for state modification should include modification information');
                }
                break;
        }
        $client = array_filter($parts, function ($v) {
            if (($v === null) || (is_array($v)) && (!sizeof($v))) {
                return false;
            }
            return true;
        });
        return $client;
    }

    /**
     * Check if client action is valid
     *
     * @return boolean
     */
    public function isValid()
    {
        switch ($this->action) {
            case 'event':
                return (boolean)strlen($this->event);
                break;
            case 'load':
                return (boolean)strlen($this->url);
                break;
            case 'state':
                return (boolean)sizeof($this->state);
                break;
        }
        return true;
    }

    /**
     * Get copy of this client action object with given modifications applied
     *
     * @param array $modifications      Modifications for client action to apply
     * @return ClientAction
     */
    public function getModified(array $modifications)
    {
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
     * @param string|array|ClientAction $action     Client action information to parse
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
                if (strpos($action, '#') !== false) {
                    $t = explode('#', $action, 2);
                    $action = array_shift($t);
                    $t = array_shift($t);
                    parse_str($t, $p);
                    if ((is_array($p)) && (sizeof($p))) {
                        $parts['state'] = $p;
                    }
                }
                if (strpos($action, '?') !== false) {
                    $t = explode('?', $action, 2);
                    $action = array_shift($t);
                    $t = array_shift($t);
                    parse_str($t, $p);
                    if ((is_array($p)) && (sizeof($p))) {
                        $parts['args'] = $p;
                    }
                }
                if (strlen($action)) {
                    if ($parts['action'] == 'event') {
                        $parts['event'] = $action;
                    } else {
                        $parts['url'] = $action;
                    }
                }
            } else {
                $parts['action'] = $action;
            }
        } else {
            if ($action instanceof ClientAction) {
                $action = $action->toArray();
            }
            if (is_array($action)) {
                foreach ($action as $name => $value) {
                    if (array_key_exists($name, $parts)) {
                        $parts[$name] = $value;
                    }
                }
            } else {
                throw new \InvalidArgumentException('Given client action information is not recognized');
            }
        }
        foreach (array('args', 'state') as $part) {
            if (!is_array($parts[$part])) {
                $parts[$part] = array();
            }
        }
        return ($parts);
    }

    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'url_generator' => null, // URL generator to use to generate URLs by given route names
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'url_generator':
                if (($value !== null) && (!$value instanceof UrlGeneratorInterface)) {
                    throw new \InvalidArgumentException('URL generator object must be instance of UrlGeneratorInterface');
                }
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }
}
