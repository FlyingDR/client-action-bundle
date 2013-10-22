<?php

namespace Flying\Bundle\ClientActionBundle\Menu\Factory;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;
use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

/**
 * Knp Menu factory extension to create client action items
 */
class ClientActionExtension implements ExtensionInterface
{

    /**
     * Builds the full option array used to configure the item.
     *
     * @param array $options The options processed by the previous extensions
     *
     * @return array
     */
    public function buildOptions(array $options)
    {
        if (!array_key_exists('extras', $options)) {
            $options['extras'] = array();
        }
        if (!array_key_exists('ca', $options['extras'])) {
            $options['extras']['ca'] = null;
        }
        // If client action is given directly, not through "extras" - move it into extras
        if ((array_key_exists('ca', $options)) && ($options['ca'] instanceof ClientAction)) {
            $options['extras']['ca'] = $options['ca'];
            unset($options['ca']);
        }
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
        $ca = null;
        if ((array_key_exists('extras', $options)) && (array_key_exists('ca', $options['extras']))) {
            $ca = $options['extras']['ca'];
        }
        if ($ca instanceof ClientAction) {
            $item->setExtra('ca', $ca);
        }
    }

}
