<?php

namespace Flying\Bundle\ClientActionBundle\Menu\Voter;

use Flying\Bundle\ClientActionBundle\Struct\ClientAction;
use Flying\Bundle\ClientActionBundle\Struct\State;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Voter for client action menu items for Knp Menu
 */
class ClientActionVoter implements VoterInterface
{
    /**
     * Client-side representation of current application state
     * @var array
     */
    protected $state;

    /**
     * Constructor
     *
     * @param State $state     Current application state
     */
    public function __construct(State $state)
    {
        $this->state = $state->toClient();
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
        $ca = $item->getExtra('ca');
        if ((!$ca instanceof ClientAction) || (!$ca->isValid())) {
            return null;
        }
        // Match client action state information against current state
        $ca = $ca->toClient();
        if (!array_key_exists('state', $ca)) {
            return null;
        }
        $matched = true;
        foreach ($ca['state'] as $name => $value) {
            if (array_key_exists($name, $this->state)) {
                if (is_array($value)) {
                    if (range(0, sizeof($value) - 1) === array_keys($value)) {
                        sort($value);
                    } else {
                        asort($value);
                    }
                }
                $sv = $this->state[$name];
                if (is_array($sv)) {
                    if (range(0, sizeof($sv) - 1) === array_keys($sv)) {
                        sort($sv);
                    } else {
                        asort($sv);
                    }
                }
                $matched &= ($value === $sv);
            } else {
                $matched = false;
            }
        }
        return (boolean)$matched;
    }
}
