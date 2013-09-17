<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures;

use Flying\Bundle\ClientActionBundle\Struct\State;

/**
 * @Struct\String(name="name", default="John")
 * @Struct\Int(name="age", nullable=true)
 * @Struct\Boolean(name="active", default=true)
 */
class SimpleState extends State implements TestStateInterface
{
    /**
     * {@inheritdoc}
     */
    public function getExpectedDefaults()
    {
        return array(
            'name'   => 'John',
            'age'    => null,
            'active' => true,
        );
    }
}
