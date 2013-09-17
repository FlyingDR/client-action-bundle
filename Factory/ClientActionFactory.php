<?php

namespace Flying\Bundle\ClientActionBundle\Factory;

use Flying\Bundle\ClientActionBundle\Struct\ClientAction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Factory for creating client action objects
 */
class ClientActionFactory
{
    /**
     * Client action class name
     * @var string
     */
    protected $class = 'Flying\Bundle\ClientActionBundle\Struct\ClientAction';
    /**
     * URLs generator to use in client action objects
     * @var UrlGeneratorInterface
     */
    protected $generator = null;

    /**
     * Class constructor
     *
     * @param string $class                         Client action class name
     * @param UrlGeneratorInterface $urlGenerator   URLs generator to use in client action objects
     * @throws \InvalidArgumentException
     * @return ClientActionFactory
     */
    public function __construct($class, UrlGeneratorInterface $urlGenerator = null)
    {
        if ((!class_exists($class, true)) ||
            (($this->class !== trim($class, '\\')) && (!is_subclass_of($class, $this->class)))
        ) {
            throw new \InvalidArgumentException('Invalid client action class name: ' . $class);
        }
        $this->class = $class;
        $this->generator = $urlGenerator;
    }

    /**
     * Create client action object by given arguments
     *
     * @param string $action        Client action to perform
     * @param string $resource      OPTIONAL Resource for client action (event name, url or route name)
     * @param string $target        OPTIONAL jQuery selector for action target
     * @param array $args           OPTIONAL Additional arguments to pass along with event or URL
     * @param array $state          OPTIONAL Changes to application state object to apply
     * @param array $config         OPTIONAL Configuration options for this client action
     * @return ClientAction
     */
    public function create($action, $resource = null, $target = null, array $args = null, array $state = null, array $config = null)
    {
        $class = $this->class;
        $config = $this->prepareConfig($config);
        $info = array(
            'action' => $action,
            'target' => $target,
            'event'  => ($action === 'event') ? $resource : null,
            'url'    => ($action === 'load') ? $resource : null,
            'args'   => $args,
            'state'  => $state,
        );
        return new $class($info, $config);
    }

    /**
     * Create client action object from its string representation
     *
     * @param string $ca            String representation of client action
     * @param array $config         OPTIONAL Configuration options for this client action
     * @return ClientAction
     */
    public function fromString($ca, array $config = null)
    {
        $class = $this->class;
        $config = $this->prepareConfig($config);
        return new $class($ca, $config);
    }

    /**
     * Prepare given configuration for client action object
     *
     * @param array $config
     * @return array
     */
    protected function prepareConfig(array $config = null)
    {
        if (!is_array($config)) {
            $config = array();
        }
        $config['url_generator'] = $this->generator;
        return $config;
    }
}
