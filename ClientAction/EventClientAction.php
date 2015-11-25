<?php

namespace Flying\Bundle\ClientActionBundle\ClientAction;

/**
 * Client action for "event" action
 *
 * @property string $event    Event to trigger as action
 *
 * @Struct\Enum(name="action", values={"event"}, default="event", nullable=false)
 * @Struct\String(name="event", nullable=true)
 */
class EventClientAction extends StateAwareClientAction
{
    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return (parent::isValid() && (boolean)strlen($this->event));
    }

    /**
     * {@inheritdoc}
     */
    protected function actionToString()
    {
        return $this->event;
    }

    /**
     * {@inheritdoc}
     */
    protected function postParse($parts)
    {
        $parts = parent::postParse($parts);
        if ($parts['action'] === 'event') {
            if ((in_array($parts['event'], array('', null), true)) &&
                (array_key_exists('contents', $parts)) && (strlen($parts['contents']))
            ) {
                $parts['event'] = $parts['contents'];
            }
            unset($parts['contents']);
        }
        return $parts;
    }
}
