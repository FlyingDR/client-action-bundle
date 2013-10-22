<?php

namespace Flying\Bundle\ClientActionBundle\Tests\State\Fixtures;

interface TestStateInterface
{
    /**
     * Get expected "default" contents of state object
     *
     * @return array
     */
    public function getExpectedDefaults();
}
