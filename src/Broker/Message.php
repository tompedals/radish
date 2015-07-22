<?php

namespace Radish\Broker;

use AMQPEnvelope;

class Message
{
    protected $appId;
    protected $body;
    protected $contentEncoding;
    protected $contentType;
    protected $deliveryMode;
    protected $deliveryTag;
    protected $exchangeName;
    protected $expiration;
    protected $headers = [];
    protected $mandatory;
    protected $messageId;
    protected $priority;
    protected $redelivery;
    protected $replyTo;
    protected $routingKey;
    protected $timestamp;
    protected $type;
    protected $userId;

    public static function createFromEnvelope(AMQPEnvelope $envelope)
    {
        $message = new self();
        $message->setAppId($envelope->getAppId());
        $message->setBody($envelope->getBody());
        $message->setContentEncoding($envelope->getContentEncoding());
        $message->setContentType($envelope->getContentType());
        $message->setDeliveryMode($envelope->getDeliveryMode());
        $message->setDeliveryTag($envelope->getDeliveryTag());
        $message->setExchangeName($envelope->getExchangeName());
        $message->setExpiration($envelope->getExpiration());
        $message->setHeaders($envelope->getHeaders());
        $message->setMessageId($envelope->getMessageId());
        $message->setPriority($envelope->getPriority());
        $message->setRedelivery($envelope->isRedelivery());
        $message->setReplyTo($envelope->getReplyTo());
        $message->setRoutingKey($envelope->getRoutingKey());
        $message->setTimestamp($envelope->getTimestamp());
        $message->setType($envelope->getType());
        $message->setUserId($envelope->getUserId());

        return $message;
    }

    public function getAttributes()
    {
        return [
            'app_id' => $this->getAppId(),
            'content_encoding' => $this->getContentEncoding(),
            'content_type' => $this->getContentType(),
            'delivery_mode' => $this->getDeliveryMode(),
            'expiration' => $this->getExpiration(),
            'headers' => $this->getHeaders(),
            'message_id' => $this->getMessageId(),
            'priority' => $this->getPriority(),
            'reply_to' => $this->getReplyTo(),
            'timestamp' => $this->getTimestamp(),
            'type' => $this->getType(),
            'user_id' => $this->getUserId(),
        ];
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getContentEncoding()
    {
        return $this->contentEncoding;
    }

    public function setContentEncoding($contentEncoding)
    {
        $this->contentEncoding = $contentEncoding;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getDeliveryMode()
    {
        return $this->deliveryMode;
    }

    public function setDeliveryMode($deliveryMode)
    {
        $this->deliveryMode = $deliveryMode;
    }

    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    public function setDeliveryTag($deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    public function getExchangeName()
    {
        return $this->exchangeName;
    }

    public function setExchangeName($exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    public function getExpiration()
    {
        return $this->expiration;
    }

    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($name, $default = null)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return $default;
    }

    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function isMandatory()
    {
        return $this->mandatory;
    }

    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool) $mandatory;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function isRedelivery()
    {
        return $this->redelivery;
    }

    public function setRedelivery($redelivery)
    {
        $this->redelivery = (bool) $redelivery;
    }

    public function getReplyTo()
    {
        return $this->replyTo;
    }

    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;
    }

    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}
