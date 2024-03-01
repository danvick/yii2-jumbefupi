<?php

namespace danvick\jumbefupi;

use yii\base\Component;

/**
 *
 * @property-read string $recipientsString
 */
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

    /**
     * @var string $scheduledAt
     */
    public $scheduledAt;

    public function getRecipientsString()
    {
        if (is_array($this->recipients)) {
            return implode(",", $this->recipients);
        }

        return (string)$this->recipients;
    }

    public function toArray()
    {
        return [
            "message" => $this->text,
            "send_at" => $this->scheduledAt,
            "recipients" => $this->recipientsString,
            "sender_id" => $this->senderId,
        ];
    }

    public function toString()
    {
        return "SenderID:\t$this->senderId\nRecipients:\t$this->recipients\nText:\t$this->text\nScheduledAt:\t$this->scheduledAt";
    }
}