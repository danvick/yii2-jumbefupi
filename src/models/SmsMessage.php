<?php

namespace danvick\jumbefupi\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sms_message".
 *
 * @property int $id
 * @property string|null $message_id
 * @property string|null $request_id
 * @property string|null $phone_number
 * @property string|null $text
 * @property int|null $sms_count
 * @property string|null $status
 */
class SmsMessage extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sms_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text'], 'string'],
            [['sms_count'], 'integer'],
            [['message_id', 'request_id'], 'string', 'max' => 64],
            [['phone_number', 'status'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'Message ID',
            'request_id' => 'Request ID',
            'phone_number' => 'Phone Number',
            'text' => 'Text',
            'sms_count' => 'Sms Count',
            'status' => 'Status',
        ];
    }
}
