<?php

namespace Flying\Bundle\ClientActionBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Application state definition annotation
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 * @Attribute("class", required=false, type="string"),
 * })
 */
class State extends ConfigurationAnnotation
{
    /**
     * Application state class name
     * @var string
     */
    protected $_class;

    /**
     * Class constructor
     *
     * @param array $values     An array of key/value parameters
     */
    public function __construct(array $values)
    {
        $this->_class = null;
        if (isset($values['value'])) {
            $values['class'] = $values['value'];
            unset($values['value']);
        }
        parent::__construct($values);
    }

    /**
     * Get application state class name
     *
     * @return string
     */
    public function getClass()
    {
        return $this->_class;
    }

    /**
     * Set application state class name
     *
     * @param string $class
     * @return void
     */
    public function setClass($class)
    {
        $this->_class = $class;
    }

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    function getAliasName()
    {
        return 'state';
    }

    /**
     * Returns whether multiple annotations of this type are allowed
     *
     * @return Boolean
     */
    function allowArray()
    {
        return false;
    }

}
