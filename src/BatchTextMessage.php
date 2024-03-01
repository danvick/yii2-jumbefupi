<?php

namespace danvick\jumbefupi;

use yii\base\Component;

class BatchTextMessage extends Component
{
    /**
     * @var TextMessage[]
     */
    public $messages;

    /**
     * @var string $senderId
     */
    public $senderId;

    /**
     * @var string $scheduledAt
     */
    public $scheduledAt;

    public function toString()
    {
        return "SenderID:\t$this->senderId\nScheduledAt:\t$this->scheduledAt";
    }
}