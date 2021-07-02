<?php

namespace danvick\jumbefupi;

use danvick\jumbefupi\models\SmsMessage;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\httpclient\Exception;

class JumbefupiGateway extends Component
{
    /**
     * @var BaseActiveRecord|string
     */
    public $model = SmsMessage::class;

    /**
     * @var string
     */
    public $senderId = 'JumbeFupi';

    /**
     * @var string
     */
    public $gatewayUsername = null;

    /**
     * @var string
     */
    public $gatewayApiKey = null;

    /**
     * @var string
     */
    public $callbackUrl = null;

    /**
     * @param string|string[] $recipients
     * @param string $text
     * @param string $senderId
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function sendMessage($recipients, $text, $senderId = null)
    {
        if ($senderId === null) {
            $senderId = $this->senderId;
        }
        if (is_array($recipients)) {
            $recipients = implode(",", $recipients);
        }
        $data = Json::encode([
            "message" => $text,
            "recipients" => $recipients,
            "sender_id" => $senderId,
            "callback_url" => $this->callbackUrl,
        ]);
        $client = new Client(['baseUrl' => 'https://api.jumbefupi.com/v2/send-message']);
        $response = $client->createRequest()
            ->setMethod('post')
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode($this->gatewayUsername . ":" . $this->gatewayApiKey)])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->addHeaders(['Content-Length' => strlen($data)])
            ->setContent($data)
            ->send();
        $responseContent = Json::decode($response->content, true);
        if (!$response->isOk) {
            throw new \yii\base\Exception("RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent) . " \nREQUEST DATA: " . VarDumper::dumpAsString($data));
        } else {
            $requestId = $responseContent['request_id'];
            $messages = $responseContent['messages'];
            $modelClass = Yii::createObject($this->model);
            foreach ($messages as $message) {
                $messageModel = new $modelClass([
                    'text' => $text,
                    'sms_count' => $message['sms_count'],
                    'message_id' => $message['message_id'],
                    'phone_number' => $message['phone_number'],
                    'status' => $message['status'],
                    'request_id' => $requestId,
                ]);
                $messageModel->save(false);
            }
            return $requestId;
        }
    }

    /**
     * @param $messageId
     * @throws \yii\base\Exception
     */
    public function getMessageStatus($messageId)
    {
        $client = new Client(['baseUrl' => 'https://api.jumbefupi.com/v2/status/message']);
        $response = $client->createRequest()
            ->setMethod('get')
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode($this->gatewayUsername . ":" . $this->gatewayApiKey)])
            ->setData(['id' => $messageId])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->send();
        $responseContent = Json::decode($response->content, true);
        if (!$response->isOk) {
            throw new \yii\base\Exception("RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent) . " \nREQUEST DATA: " . VarDumper::dumpAsString($data));
        } else {
            $modelClass = Yii::createObject($this->model);
            $message = $modelClass::findOne(['message_id' => $messageId]);
            $message->status = $responseContent['status'];
            $message->save(false);
        }
    }

    /**
     * @return bool|string
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function checkSmsBalance()
    {
        $client = new Client(['baseUrl' => 'https://api.jumbefupi.com/v2/balance']);
        $client = $client->createRequest()
            ->setMethod('get')
            ->addHeaders(['Content-Type' => 'application/text'])
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode($this->gatewayUsername . ":" . $this->gatewayApiKey)]);
        $response = $client->send();
        if ($response->isOk) {
            $decodedResponse = Json::decode($response->content);
            return $decodedResponse['balance'];
        }
        return false;
    }
}