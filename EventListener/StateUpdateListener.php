<?php

namespace Flying\Bundle\ClientActionBundle\EventListener;

use Flying\Bundle\ClientActionBundle\ClientAction\StateClientAction;
use Flying\Bundle\ClientActionBundle\State\State;
use Flying\Bundle\ClientActionBundle\State\StateSubscriberInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Application state update service performs automatic
 * applying of request parameters related to application state
 * to current application state object
 */
class StateUpdateListener implements EventSubscriberInterface, StateSubscriberInterface
{
    /**
     * Current application state
     * @var State
     */
    protected $state;
    /**
     * Name of request parameter that container state modifications
     * @var string
     */
    protected $stateParamName = '__state';

    /**
     * Constructor
     *
     * @param string $stateParamName OPTIONAL Name of request parameter that container state modifications
     */
    public function __construct($stateParamName = null)
    {
        if ($stateParamName !== null) {
            $this->stateParamName = $stateParamName;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setState(State $state)
    {
        $this->state = $state;
    }

    /**
     * Resolve application state object associated with request
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @throws \RuntimeException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $bag = null;
        if ($request->request->has($this->stateParamName)) {
            $bag = $request->request;
        } elseif ($request->query->has($this->stateParamName)) {
            $bag = $request->query;
        }
        if (!$bag) {
            return;
        }
        $params = $bag->get($this->stateParamName);
        $bag->remove($this->stateParamName);
        if (!is_array($params)) {
            return;
        }
        if (!$this->state instanceof State) {
            // No application state is available
            return;
        }
        $ca = new StateClientAction(array('state' => $params));
        $this->state->set($ca->state->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // It is important that priority of this service
        // is lower then priority of StateInitListener service
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', -110),
        );
    }
}
