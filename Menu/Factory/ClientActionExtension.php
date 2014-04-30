<?php

namespace Flying\Bundle\ClientActionBundle\Menu\Factory;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;
use Flying\Bundle\ClientActionBundle\Factory\ClientActionFactory;
use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

/**
 * Knp Menu factory extension to create client action items
 */
class ClientActionExtension implements ExtensionInterface
{
    /**
     * @var ClientActionFactory
     */
    protected $factory;

    /**
     * @param ClientActionFactory $factory
     */
    function __construct(ClientActionFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Builds the full option array used to configure the item.
     *
     * @param array $options The options processed by the previous extensions
     *
     * @return array
     */
    public function buildOptions(array $options)
    {
        $ca = null;
        if (array_key_exists('client_action', $options)) {
            $ca = $options['client_action'];
            unset($options['client_action']);
        } elseif (array_key_exists('ca', $options)) {
            $ca = $options['ca'];
            unset($options['ca']);
        } elseif ((array_key_exists('extras', $options)) && (is_array($options['extras']))) {
            if (array_key_exists('client_action', $options['extras'])) {
                $ca = $options['extras']['client_action'];
            } elseif (array_key_exists('ca', $options['extras'])) {
                $ca = $options['extras']['ca'];
                unset($options['extras']['ca']);
            }
        }
        $options['extras']['client_action'] = $ca;
        return $options;
    }

    /**
     * Configures the item with the passed options
     *
     * @param ItemInterface $item
     * @param array $options
     */
    public function buildItem(ItemInterface $item, array $options)
    {
        $ca = $options['extras']['client_action'];
        if ($ca === null) {
            return;
        }
        if (!$ca instanceof ClientAction) {
            $ca = $this->factory->create($ca);
        }
        if ($ca->isValid()) {
            $item->setExtra('client_action', $ca);
            $attrs = array();
            $la = $item->getLabelAttributes();
            array_walk($la, function ($v, $k) use (&$attrs) {
                if (!preg_match('/^data\-ca\-/i', $k)) {
                    $attrs[$k] = $v;
                }
            });
            $attrs = array_merge($attrs, $ca->toAttrs());
            $item->setLabelAttributes($attrs);
        }
    }

}
