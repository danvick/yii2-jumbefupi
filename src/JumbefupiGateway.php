<?php

namespace danvick\jumbefupi;

use danvick\jumbefupi\models\SmsMessage;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Connection;
use yii\db\Expression;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\httpclient\Exception;

/**
 *
 * @property-read Client $httpClient
 */
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
    public $gatewayUsername;

    /**
     * @var string
     */
    public $gatewayApiKey;

    /**
     * @var string
     */
    public $callbackUrl;

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
     * @var bool
     */
    public $useFileTransport = false;

    /**
     * @var string
     */
    public $fileTransportPath = '@runtime/messages';

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
     * @param TextMessage $message
     * @return bool|mixed
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function send($message)
    {
        if ($this->useFileTransport) {
            return $this->saveMessage($message);
        }

        return $this->sendMessage($message);
    }

    /**
     * @param BatchTextMessage $batch
     * @return bool|mixed
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function sendBatch($batch)
    {
        /*if ($this->useFileTransport) {
            return $this->saveBatch($batch);
        }*/

        return $this->sendMessagesBatch($batch);
    }

    /**
     * @param TextMessage $message
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    protected function sendMessage($message)
    {
        $data = Json::encode(ArrayHelper::merge($message->toArray(), [
            "sender_id" => $message->senderId ?: $this->senderId,
            "callback_url" => $this->callbackUrl,
        ]));
        $response = $this->getHttpClient()->post('send-message')
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode("$this->gatewayUsername:$this->gatewayApiKey")])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->addHeaders(['Content-Length' => strlen($data)])
            ->setContent($data)
            ->send();
        $responseContent = Json::decode($response->content);
        if (!$response->isOk) {
            Yii::error("RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent) . " \nREQUEST DATA: " . VarDumper::dumpAsString($data));
            throw new \yii\base\Exception($responseContent['message']);
        }
        return $this->processSendMessageResponse($responseContent);
    }

    /**
     * @param BatchTextMessage $batch
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    protected function sendMessagesBatch($batch)
    {
        $messages = [];
        foreach ($batch->messages as $message) {
            $messages[] = $message->toArray();
        }

        $data = Json::encode([
            "messages" => $messages,
            "send_at" => $batch->scheduledAt,
            "sender_id" => $batch->senderId ?: $this->senderId,
            "callback_url" => $this->callbackUrl,
        ]);
        $response = $this->getHttpClient()->post('send-message/batch')
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode("$this->gatewayUsername:$this->gatewayApiKey")])
            ->addHeaders(['Content-Type' => 'application/json'])
            ->addHeaders(['Content-Length' => strlen($data)])
            ->setContent($data)
            ->send();
        $responseContent = Json::decode($response->content);
        if (!$response->isOk) {
            Yii::error("RESPONSE ERROR: " . VarDumper::dumpAsString($responseContent) . " \nREQUEST DATA: " . VarDumper::dumpAsString($data));
            throw new \yii\base\Exception($responseContent['message']);
        }
        return $this->processSendMessageResponse($responseContent);
    }

    /**
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    protected function processSendMessageResponse($responseContent){
        Yii::$app->cache->delete($this->balanceCacheKey);
        $requestId = $responseContent['request_id'];
        $messages = $responseContent['messages'];
        /** @var ActiveRecord $modelClass */
        $modelClass = Yii::createObject($this->model);
        $messageModels = [];
        foreach ($messages as $textMessage) {
            $messageModel = new $modelClass([
                'text' => $textMessage['text'],
                'sms_count' => $textMessage['sms_count'],
                'message_id' => $textMessage['message_id'],
                'phone_number' => $textMessage['phone_number'],
                'status' => $textMessage['status'],
                // 'scheduled_at' => $textMessage['send_at'],
                'request_id' => $requestId,
                'created_at' => new Expression('NOW()'),
                'updated_at' => new Expression('NOW()'),
                'created_by' => isset(Yii::$app->user->id) ? Yii::$app->user->id : null,
                'updated_by' => isset(Yii::$app->user->id) ? Yii::$app->user->id : null,
            ]);
            $messageModels[] = $messageModel;
        }
        $this->db->createCommand()->batchInsert($modelClass::tableName(), (new $modelClass())->attributes(), $messageModels)->execute();

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
        /** @var ActiveRecord $modelClass */
        $modelClass = Yii::createObject($this->model);
        $message = $modelClass::find()->where(['message_id' => $messageId])->one($this->db);
        if ($message) {
            $message->status = $responseContent['status'];
            $message->save(false);
        }
        return $responseContent['status'];
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
            if ($cachedValue !== false) {
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

    /**
     * @param TextMessage $message
     * @return String
     * @throws \Exception
     */
    protected function saveMessage($message)
    {
        $path = Yii::getAlias($this->fileTransportPath);
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        $messageFilename = $this->generateMessageFileName(); // TODO: Use UUID?
        $file = $path . '/' . $messageFilename;
        file_put_contents($file, $message->toString());

        return $messageFilename;
    }

    /**
     * @return string the file name for saving the message when [[useFileTransport]] is true.
     * @throws \Exception
     */
    protected function generateMessageFileName()
    {
        $time = microtime(true);

        return date('Ymd-His-', (int)$time) . sprintf('%04d', (int)(($time - (int)$time) * 10000)) . '-' . sprintf('%04d', mt_rand(0, 10000)) . '.txt';
    }
}