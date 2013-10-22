<?php

namespace Flying\Bundle\ClientActionBundle\State;

use Flying\Struct\Common\ComplexPropertyInterface;
use Flying\Struct\Property\Property;
use Flying\Struct\StorableStruct;
use Flying\Struct\Struct;

/**
 * Base class for application state classes
 */
class State extends StorableStruct implements ClientExportInterface
{

    /**
     * Get application state representation suitable for client side of application
     *
     * @return array
     */
    public function toClient()
    {
        return $this->structToClient($this);
    }

    /**
     * Convert given structure to its client side representation
     *
     * @param Struct $struct    Structure to convert
     * @param string $prefix    OPTIONAL Prefix to prepend to structure's keys
     * @return array
     */
    protected function structToClient(Struct $struct, $prefix = '')
    {
        if ((strlen($prefix)) && (substr($prefix, -1) !== '.')) {
            $prefix .= '.';
        }
        $client = array();
        foreach ($struct as $key => $value) {
            $property = $struct->getProperty($key);
            if ($property instanceof Struct) {
                $child = $this->structToClient($property, $prefix . $key);
                $client = array_merge($client, $child);
            } elseif ($property instanceof ClientExportInterface) {
                $client[$prefix . $key] = $property->toClient();
            } elseif ($property instanceof Property) {
                $client[$prefix . $key] = ($value instanceof ComplexPropertyInterface) ? $value->toArray() : $value;
            }
        }
        return $client;
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

    /**
     * Perform "lazy initialization" of configuration option with given name
     *
     * @param string $name Configuration option name
     * @return mixed
     */
    protected function lazyConfigInit($name)
    {
        switch ($name) {
            case 'explicit_metadata_class':
                // App.state structures should have at least base functionality of app.state
                return __CLASS__;
            default:
                return parent::lazyConfigInit($name);
                break;
        }
    }
}
