<?php

namespace Flying\Bundle\ClientActionBundle\Factory;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;

/**
 * Factory for creating client action objects
 *
 * Client action can be defined as URI-like form:
 *
 * [action ":"] ["[" target "]"]? [content]? ["?" args]? ["#" state]?
 *
 * See examples about details of format:
 *
 * Set application state object properties:
 * state:?param1=value1&param2=value2
 * state:toggle?collection=value
 *
 * Trigger event "eventName":
 * event:eventName
 *
 * Trigger event "eventName" with specified additional arguments:
 * event:eventName?arg1=value1&arg2=value2
 *
 * Trigger event "eventName" to "#targetId" page element and additional arguments:
 * event:[#targetId]eventName?arg1=value1&arg2=value2
 *
 * Load information from server from "/some/url/path" using GET method into "#targetId" page element with additional arguments:
 * load:[#targetId]{method:GET}/some/url/path?arg1=value1&arg2=value2
 *
 * Load information from URL generated for "my_route" route with additional arguments into #targetId target:
 * load:[#targetId]my_route?param1=value1&param2=value2
 */
class ClientActionFactory
{
    /**
     * List of registered client action classes
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->actions = array();
    }

    /**
     * Register client action type
     *
     * @param ClientAction $action Reference implementation of client action of given type
     * @param string $type         OPTIONAL Client action type
     * @return void
     */
    public function registerAction(ClientAction $action, $type)
    {
        $this->actions[strtolower($type)] = $action;
    }

    /**
     * Create client action object by given information
     *
     * @param string|array|ClientAction $info Client action information to use to create new client action
     * @param array $config                   OPTIONAL Configuration options for new client action object
     * @throws \InvalidArgumentException
     * @return ClientAction
     */
    public function create($info, array $config = null)
    {
        $type = null;
        if ($info instanceof ClientAction) {
            $type = $info->action;
        } elseif (is_array($info)) {
            $type = (array_key_exists('action', $info)) ? $info['action'] : null;
        } elseif (is_string($info)) {
            $t = explode(':', $info, 2);
            $type = array_shift($t);
        }
        if (!$type) {
            throw new \InvalidArgumentException('Unable to recognize given type of client action information');
        }
        $type = strtolower($type);
        if (!array_key_exists($type, $this->actions)) {
            throw new \InvalidArgumentException('Unknown client action type: ' . $type);
        }
        /** @var $ca ClientAction */
        $ca = $this->actions[$type];
        $config = $ca->modifyConfig(array(), $config);
        $class = get_class($ca);
        $ca = new $class($info, $config);
        return $ca;
    }
}
