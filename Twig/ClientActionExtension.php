<?php

namespace Flying\Bundle\ClientActionBundle\Twig;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;
use Flying\Bundle\ClientActionBundle\Factory\ClientActionFactory;
use Flying\Bundle\ClientActionBundle\State\State;

/**
 * Twig extension to render client actions
 */
class ClientActionExtension extends \Twig_Extension
{
    /**
     * @var ClientActionFactory
     */
    protected $factory;

    /**
     * @param ClientActionFactory $factory
     */
    public function __construct(ClientActionFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('ca', array($this, 'renderCa'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('ca_state', array($this, 'renderCaState'), array('is_safe' => array('html'))),
        );
    }

    /**
     * Render given client action as a set of HTML attributes
     *
     * @param ClientAction|array|string $ca
     * @param ClientAction|array|string $modifications Modifications for client action to apply
     * @return string
     */
    public function renderCa($ca, $modifications = null)
    {
        if (!$ca instanceof ClientAction) {
            $ca = $this->factory->create($ca);
        }
        if ($modifications !== null) {
            $ca = $ca->getModified($modifications);
        }
        $html = array();
        $attrs = $ca->toAttrs();
        foreach ($attrs as $k => $v) {
            $html[] = $k . '="' . htmlentities($v, ENT_COMPAT, 'utf-8') . '"';
        }
        return ' ' . join(' ', $html);
    }

    /**
     * Render given application state object in a way required by JS side of the bundle
     *
     * @param State $state
     * @return string
     */
    public function renderCaState(State $state)
    {
        $js = array(
            'id'      => $state->getId(),
            'default' => $state->getDefaults(),
            'current' => $state->getModifications(),
        );
        return json_encode($js);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'client_action';
    }
}
