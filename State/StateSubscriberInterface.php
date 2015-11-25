<?php

namespace Flying\Bundle\ClientActionBundle\State;

interface StateSubscriberInterface
{
    /**
     * Set current application state
     *
     * @param State $state Current application state
     * @return void
     */
    public function setState(State $state);
}
