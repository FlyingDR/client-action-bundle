<?php

namespace Flying\Bundle\ClientActionBundle\ClientAction;

use Flying\Bundle\ClientActionBundle\State\State;
use Flying\Struct\Property\Boolean;
use Flying\Struct\Property\Collection;
use Flying\Struct\Property\PropertyInterface;
use Flying\Struct\Struct;
use Flying\Struct\StructInterface;

/**
 * Client action for "state" action
 *
 * @property string $operation      Application state modification operation to perform
 * @property Collection $state      State modifications for client action
 *
 * @Struct\Enum(name="action", values={"state"}, default="state", nullable=false)
 * @Struct\Enum(name="operation", values={"reset","set","modify","toggle"}, default="modify", nullable=false)
 * @Struct\Collection(name="state")
 */
class StateClientAction extends ClientAction
{
    /**
     * @var array
     */
    protected $stateOperations = array(
        'reset'  => '!',
        'set'    => '=',
        'modify' => '',
        'toggle' => '~',
    );

    /**
     * {@inheritdoc}
     */
    protected function actionToString()
    {
        $result = $this->operation;
        if ($this->state->count()) {
            $result .= '?' . $this->buildQueryString($this->state->toArray());
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toClient()
    {
        $client = parent::toClient();
        if (array_key_exists('state', $client)) {
            $client['state'] = $this->toPlainArray($client['state']);
        }
        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return (($this->operation == 'reset') || (boolean)sizeof($this->state));
    }

    /**
     * Apply app.state modifications to given app.state object
     *
     * @param State $state
     * @reset void
     */
    public function apply(State $state)
    {
        switch ($this->operation) {
            case 'reset':
                $state->reset();
                break;
            case 'set':
                $state->reset();
                $state->set($this->state->toArray());
                break;
            case 'modify':
            case 'toggle':
                $this->applyStateModifications($state, $this->state->toArray(), $this->operation);
                break;
        }
    }

    /**
     * Apply given modifications to given app.state object
     *
     * @param Struct $state
     * @param array $modifications
     * @return void
     */
    protected function applyStateModifications(Struct $state, array $modifications)
    {
        foreach ($modifications as $name => $value) {
            $property = $state->getProperty($name);
            if ($property instanceof StructInterface) {
                // Embedded structure
                if (is_array($value)) {
                    /** @var $property Struct */
                    $this->applyStateModifications($property, $value, $this->operation);
                }
            } elseif ($property instanceof Collection) {
                // Collection property
                if (is_array($value)) {
                    $property->reset();
                    $property->setValue($value);
                } else {
                    switch ($this->operation) {
                        case 'modify':
                            $property->add($value);
                            break;
                        case 'toggle':
                            $property->toggle($value);
                            break;
                    }
                }
            } elseif ($property instanceof PropertyInterface) {
                // Simple property
                if (($property instanceof Boolean) && ($this->operation === 'toggle')) {
                    $property->setValue(!$property->getValue());
                } else {
                    $property->setValue($value);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function preParse(&$action, $parts)
    {
        $parts = parent::preParse($action, $parts);
        if (is_string($action)) {
            if (preg_match('/^(.+?)#([\!\=\~])?((?:[a-z0-9\[\]\.\%\+\-\_]+\=[a-z0-9\[\]\.\%\+\-\_]+\&?)*)$/Usi', $action, $t)) {
                $ca = $t[1];
                $operation = $t[2];
                $state = $t[3];
                $ops = array_flip($this->stateOperations);
                if ((strlen($operation)) && (array_key_exists($operation, $ops))) {
                    $parts['operation_flag'] = $ops[$operation];
                }
                $parts['state'] = $this->parseQueryString($state);
                $action = $ca;
            }
        }
        return $parts;
    }

    /**
     * {@inheritdoc}
     */
    protected function postParse($parts)
    {
        $parts = parent::postParse($parts);
        if ($parts['action'] == 'state') {
            if (!strlen($parts['operation'])) {
                if ((array_key_exists('contents', $parts)) && (strlen($parts['contents']))) {
                    $parts['operation'] = $parts['contents'];
                } elseif ((array_key_exists('operation_flag', $parts)) && (strlen($parts['operation_flag']))) {
                    $parts['operation'] = $parts['operation_flag'];
                }
                unset($parts['operation_flag']);
            }
            if ((!is_array($parts['state'])) && (is_array($parts['args']))) {
                $parts['state'] = $parts['args'];
            }
            unset($parts['target']);
            unset($parts['args']);
            unset($parts['contents']);
        }
        if (!is_array($parts['state'])) {
            $parts['state'] = array();
        }
        return $parts;
    }
}
