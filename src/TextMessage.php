<?php

namespace danvick\jumbefupi;

use yii\base\Component;

class TextMessage extends Component
{
    /**
     * @var string|string[] $recipients
     */
    public $recipients;

    /**
     * @var string $text
     */
    public $text;

    /**
     * @var string $senderId
     */
    public $senderId;

    public function toString()
    {
        return "SenderID:\t$this->senderId\nRecipients:\t$this->recipients\nText:\t$this->text";
    }
}