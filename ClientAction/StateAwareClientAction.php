<?php

namespace Flying\Bundle\ClientActionBundle\ClientAction;

/**
 * Base class for client actions that can operate with application state modifications
 */
abstract class StateAwareClientAction extends StateClientAction
{
    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        $string = parent::toString();
        // State operation can't be exposed as client action content
        // so it should be always stored using state operation marker
        if ($this->state->count()) {
            $state = $this->buildQueryString($this->state->toArray());
            $operation = strtr($this->operation, $this->stateOperations);
            $string .= '#' . $operation . $state;
        }
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return ($this->state->count()) ? parent::isValid() : true;
    }

    /**
     * {@inheritdoc}
     */
    public function toClient()
    {
        $client = parent::toClient();
        // "operation" parameter should only be sent to client if it is really used in this client action
        if ((!array_key_exists('state', $client)) && (array_key_exists('operation', $client))) {
            if ($client['operation'] !== 'reset') {
                unset($client['operation']);
            }
        }
        return $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function postParse($parts)
    {
        $parts = parent::postParse($parts);
        if (!strlen($parts['operation']) &&
            (array_key_exists('operation_flag', $parts)) &&
            (strlen($parts['operation_flag']))
        ) {
            $parts['operation'] = $parts['operation_flag'];
            unset($parts['operation_flag']);
        }
        return $parts;
    }
}
