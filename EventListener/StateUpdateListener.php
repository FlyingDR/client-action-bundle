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
     *
     * @var State
     */
    protected $state;
    /**
     * Name of request parameter that container state modification operation
     *
     * @var string
     */
    protected $stateOperationParamName = '__operation';
    /**
     * Name of request parameter that container state modifications
     *
     * @var string
     */
    protected $stateChangesParamName = '__state';

    /**
     * Constructor
     *
     * @param string $stateOperationParamName OPTIONAL Name of request parameter that container state modification operation
     * @param string $stateChangesParamName   OPTIONAL Name of request parameter that container state modifications
     */
    public function __construct($stateOperationParamName = null, $stateChangesParamName = null)
    {
        if ($stateOperationParamName !== null) {
            $this->stateOperationParamName = $stateOperationParamName;
        }
        if ($stateChangesParamName !== null) {
            $this->stateChangesParamName = $stateChangesParamName;
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
        if (!$this->state instanceof State) {
            // No application state is available
            return;
        }
        $request = $event->getRequest();
        $bag = null;
        if ($request->request->has($this->stateChangesParamName)) {
            $bag = $request->request;
        } elseif ($request->query->has($this->stateChangesParamName)) {
            $bag = $request->query;
        }
        if (!$bag) {
            return;
        }
        $operation = $request->request->get($this->stateOperationParamName);
        $bag->remove($this->stateOperationParamName);
        $state = $bag->get($this->stateChangesParamName);
        $bag->remove($this->stateChangesParamName);
        if (is_array($state)) {
            $ca = array(
                'operation' => $operation,
                'state'     => $state,
            );
        } else {
            $ca = 'state:' . $operation . '?' . $state;
        }
        $ca = new StateClientAction($ca);
        $ca->apply($this->state);
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
