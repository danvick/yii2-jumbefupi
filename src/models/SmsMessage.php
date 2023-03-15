<?php

namespace danvick\jumbefupi\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

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
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $created_at
 * @property string|null $updated_at
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
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::class,
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text'], 'string'],
            [['sms_count', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
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
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
