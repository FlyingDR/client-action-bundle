<?php

namespace Flying\Bundle\ClientActionBundle\Menu\Voter;

use Flying\Bundle\ClientActionBundle\ClientAction\ClientAction;
use Flying\Bundle\ClientActionBundle\State\State;
use Flying\Bundle\ClientActionBundle\State\StateSubscriberInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;

/**
 * Voter for client action menu items for Knp Menu
 */
class ClientActionVoter implements VoterInterface, StateSubscriberInterface
{
    /**
     * Client-side representation of current application state
     *
     * @var State
     */
    protected $state;
    /**
     * Cached representation of client state as array
     *
     * @var array
     */
    protected $stateArray;

    /**
     * {@inheritdoc}
     */
    public function setState(State $state)
    {
        $this->state = $state;
        $this->stateArray = null;
    }

    protected function getStateArray()
    {
        if (!is_array($this->stateArray)) {
            $this->stateArray = $this->state->toClient();
        }
        return $this->stateArray;
    }

    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     *
     * @param ItemInterface $item
     *
     * @return boolean|null
     */
    public function matchItem(ItemInterface $item)
    {
        $ca = $item->getExtra('client_action');
        if ((!$ca instanceof ClientAction) || (!$ca->isValid())) {
            return null;
        }
        // Match client action state information against current state
        $ca = $ca->toClient();
        if (!array_key_exists('state', $ca)) {
            return null;
        }
        $state = $this->getStateArray();
        $matched = true;
        foreach ($ca['state'] as $name => $value) {
            if (array_key_exists($name, $state)) {
                if (is_array($value)) {
                    if (range(0, count($value) - 1) === array_keys($value)) {
                        sort($value);
                    } else {
                        asort($value);
                    }
                }
                $sv = $state[$name];
                if (is_array($sv)) {
                    if (range(0, count($sv) - 1) === array_keys($sv)) {
                        sort($sv);
                    } else {
                        asort($sv);
                    }
                }
                if ((is_bool($sv)) && ($ca['operation'] === 'toggle')) {
                    // Boolean value toggling, element is active if current value is active
                    $matched &= ($sv === true);
                } else {
                    $matched &= ($value === $sv);
                }
            } else {
                $matched = false;
            }
        }
        return (boolean)$matched;
    }
}
