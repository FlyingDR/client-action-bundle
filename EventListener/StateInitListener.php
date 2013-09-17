<?php

namespace Flying\Bundle\ClientActionBundle\EventListener;

use Flying\Bundle\ClientActionBundle\Annotation\State as StateAnnotation;
use Flying\Bundle\ClientActionBundle\Struct\State;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * Constructor
     *
     * @param ContainerInterface $container     The service container instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            $state = new $class();
            if (!$state instanceof State) {
                throw new \RuntimeException('Application state object must be instance of State');
            }
        }
        $this->container->set('client_action.state', $state);
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
