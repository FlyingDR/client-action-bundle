<?php

namespace Flying\Bundle\ClientActionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Application state definition annotation
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *  @Attribute("class", required=false, type="string"),
 * })
 */
class State extends ConfigurationAnnotation
{
    /**
     * Application state class name
     *
     * @var string
     */
    protected $class;

    /**
     * Class constructor
     *
     * @param array $values An array of key/value parameters
     */
    public function __construct(array $values)
    {
        $this->class = null;
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
        return $this->class;
    }

    /**
     * Set application state class name
     *
     * @param string $class
     * @return void
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    public function getAliasName()
    {
        return 'state';
    }

    /**
     * Returns whether multiple annotations of this type are allowed
     *
     * @return Boolean
     */
    public function allowArray()
    {
        return false;
    }
}
