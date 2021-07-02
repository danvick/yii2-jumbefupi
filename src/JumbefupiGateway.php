<?php

namespace danvick\jumbefupi;

use danvick\jumbefupi\models\SmsMessage;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\db\BaseActiveRecord;
use yii\db\Connection;
use yii\di\Instance;
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
     * @var bool
     */
    public $cacheBalance = false;

    /**
     * @var string
     */
    public $balanceCacheKey = 'JUMBEFUPI_BALANCE';

    /**
     * @var Cache|array|string
     */
    public $cache = 'cache';

    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    /**
     * @inheritdoc
     * @throws \yii\base\Exception
     */
    public function init()
    {
        parent::init();
        if (empty($this->gatewayUsername)) {
            throw new \yii\base\Exception('Jumbefupi Username must be set');
        }
        if (empty($this->gatewayApiKey)) {
            throw new \yii\base\Exception('Jumbefupi API key must be set');
        }
        $this->cache = Instance::ensure($this->cache, Cache::class);
        $this->db = Instance::ensure($this->db, Connection::class);
        // $this->model = Instance::ensure($this->model, BaseActiveRecord::class);
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return new Client(['baseUrl' => 'https://api.jumbefupi.com/v2']);
    }

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
        $response = $this->getHttpClient()->post('send-message')
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode("$this->gatewayUsername:$this->gatewayApiKey")])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->addHeaders(['Content-Length' => strlen($data)])
            ->setContent($data)
            ->send();
        $responseContent = Json::decode($response->content, true);
        if (!$response->isOk) {
            Yii::error("RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent) . " \nREQUEST DATA: " . VarDumper::dumpAsString($data));
            throw new \yii\base\Exception($responseContent['message']);
        }
        Yii::$app->cache->delete($this->balanceCacheKey);
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

    /**
     * @param $messageId
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getMessageStatus($messageId)
    {
        $response = $this->getHttpClient()->get('status/message', ['id' => $messageId])
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode("$this->gatewayUsername:$this->gatewayApiKey")])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->send();
        $responseContent = Json::decode($response->content, true);
        if (!$response->isOk) {
            Yii::error("JUMBEFUPI RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent));
            throw new \yii\base\Exception($responseContent['message']);
        }
        $modelClass = Yii::createObject($this->model);
        $message = $modelClass::findOne(['message_id' => $messageId]);
        $message->status = $responseContent['status'];
        $message->save(false);
        return $message->status;
    }

    /**
     * @param bool $fromCache
     * @return bool|string
     * @throws \yii\base\Exception
     */
    public function checkBalance($fromCache = true)
    {
        if ($fromCache && $this->cacheBalance) {
            $cachedValue = $this->cache->get($this->balanceCacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }
        $response = $this->getHttpClient()->get('balance')
            ->addHeaders(['Content-Type' => 'application/text'])
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode("$this->gatewayUsername:$this->gatewayApiKey")])
            ->send();
        if (!$response->isOk) {
            Yii::error("JUMBEFUPI RESPONSE ERROR: " . VarDumper::dumpAsString(Json::decode($response->content)));
            throw new \yii\base\Exception(Json::decode($response->content)['message']);
        }
        $balance = Json::decode($response->content)['balance'];
        if ($this->cacheBalance) {
            $this->cache->add($this->balanceCacheKey, $balance);
        }
        return $balance;
    }
}