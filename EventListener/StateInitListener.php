<?php

namespace Flying\Bundle\ClientActionBundle\EventListener;

use Flying\Bundle\ClientActionBundle\Annotation\State as StateAnnotation;
use Flying\Bundle\ClientActionBundle\State\State;
use Flying\Bundle\ClientActionBundle\State\StateSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Application state initialization service
 * handles @State annotation in controllers
 */
class StateInitListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * Subscribers to state initialization
     *
     * @var array
     */
    protected $subscribers = array();

    /**
     * Constructor
     *
     * @param ContainerInterface $container The service container instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->subscribers = array();
    }

    /**
     * Register service that wants to receive application state object
     * as soon as it will be determined
     *
     * @param StateSubscriberInterface $subscriber
     * @return void
     */
    public function addStateSubscriber(StateSubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * Resolve application state object associated with request
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @throws \RuntimeException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $configuration = null;
        $class = null;
        $state = null;
        $request = $event->getRequest();
        if ($request->attributes->has('_state')) {
            $configuration = $request->attributes->get('_state');
        }
        if (!$configuration) {
            return;
        }
        if ($configuration instanceof State) {
            $state = $configuration;
        } elseif ($configuration instanceof StateAnnotation) {
            $class = $configuration->getClass();
        } elseif (is_string($configuration)) {
            $class = $configuration;
        }
        if (!$state) {
            if (!$class) {
                throw new \RuntimeException('Application state information is not recognized');
            }
            if (!class_exists($class)) {
                // @TODO Move namespaces resolution into container compiler pass
                $sn = trim($class, '\\');
                $class = null;
                $namespaces = $this->container->get('client_action.state.nsmap')->getAll();
                foreach ($namespaces as $ns) {
                    $fqcn = $ns . '\\' . $sn;
                    if (class_exists($fqcn, true)) {
                        $class = $fqcn;
                        break;
                    }
                }
                if (!$class) {
                    throw new \RuntimeException('Unable to find application state class "' . $sn . '"');
                }
            }
            try {
                $cState = $this->container->get('client_action.state');
                if (get_class($cState) === $class) {
                    // We're already have required state class in container
                    return;
                }
            } catch (RuntimeException $e) {
                // This exception can be safely ignored because it is expected at a time
                // when synthetic state service is not defined yet
            }
            if (!$state) {
                $state = new $class();
            }
            if (!$state instanceof State) {
                throw new \RuntimeException('Application state object must be instance of State');
            }
        }
        $this->container->set('client_action.state', $state);
        /** @var $subscriber StateSubscriberInterface */
        foreach ($this->subscribers as $subscriber) {
            $subscriber->setState($state);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', -100),
        );
    }
}
