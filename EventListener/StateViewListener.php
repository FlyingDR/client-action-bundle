<?php

namespace Flying\Bundle\ClientActionBundle\EventListener;

use Flying\Bundle\ClientActionBundle\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provider for current app.state object into view
 */
class StateViewListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var string
     */
    protected $stateVarName = 'state';

    /**
     * Constructor
     *
     * @param ContainerInterface $container The service container instance
     * @param string $stateVarName          Name of template variable to store state in
     */
    public function __construct(ContainerInterface $container, $stateVarName = 'state')
    {
        $this->container = $container;
        $this->stateVarName = $stateVarName;
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $parameters = $event->getControllerResult();
        if ($parameters === null) {
            $parameters = array();
        }
        if (is_array($parameters)) {
            if ((!array_key_exists($this->stateVarName, $parameters)) ||
                (!$parameters[$this->stateVarName] instanceof State)
            ) {
                $state = null;
                try {
                    $state = $this->container->get('client_action.state');
                } catch (RuntimeException $e) {
                    $state = new State();
                }
                $parameters[$this->stateVarName] = $state;
            }
            $event->setControllerResult($parameters);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array('onKernelView', 100),
        );
    }
}
