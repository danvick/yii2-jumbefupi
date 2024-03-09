<?php

namespace danvick\jumbefupi;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

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
        return "SenderID:\t$this->senderId\nScheduledAt:\t$this->scheduledAt\nMessages:\t" . Json::encode(ArrayHelper::toArray($this->messages), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}