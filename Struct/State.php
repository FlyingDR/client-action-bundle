<?php

namespace Flying\Bundle\ClientActionBundle\Struct;

use Flying\Struct\Common\ComplexPropertyInterface;
use Flying\Struct\Property\Property;
use Flying\Struct\StorableStruct;
use Flying\Struct\Struct;

/**
 * Base class for application state classes
 */
class State extends StorableStruct
{

    /**
     * Get application state representation suitable for client side of application
     *
     * @return array
     */
    public function toClient()
    {
        return $this->toArray();
    }

    /**
     * Get application state representation into its default state
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->getStructDefaults($this);
    }

    /**
     * Get "default" state of given structure
     *
     * @param Struct $struct
     * @return array
     */
    protected function getStructDefaults(Struct $struct)
    {
        $defaults = array();
        foreach ($struct as $key => $value) {
            $value = $struct->getProperty($key);
            if ($value instanceof Struct) {
                $defaults[$key] = $this->getStructDefaults($value);
            } elseif ($value instanceof Property) {
                $defaults[$key] = $value->getConfig('default');
            }
        }
        return $defaults;
    }

    /**
     * Get list of modifications of application state structure against its default state
     *
     * @return array
     */
    public function getModifications()
    {
        return $this->getStructModifications($this);
    }

    /**
     * Get state modifications of given structure
     *
     * @param Struct $struct
     * @return array
     */
    protected function getStructModifications(Struct $struct)
    {
        $modifications = array();
        foreach ($struct as $key => $value) {
            $property = $struct->getProperty($key);
            if ($property instanceof Struct) {
                $m = $this->getStructModifications($property);
                if (sizeof($m)) {
                    $modifications[$key] = $m;
                }
            } elseif ($property instanceof Property) {
                if ($value instanceof ComplexPropertyInterface) {
                    $value = $value->toArray();
                }
                $default = $property->getConfig('default');
                if ($value !== $default) {
                    $modifications[$key] = $value;
                }
            }
        }
        return $modifications;
    }
}
