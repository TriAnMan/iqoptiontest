<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 20:11
 */

namespace TriAn\IqoTest\core;


class Message
{
    public $uuid;
    public $dNum;
    protected $rawBody;
    protected $body;

    public function __construct($uuid, $dNum)
    {
        $this->uuid = $uuid;
        $this->dNum = $dNum;
    }

    /**
     * @param $blob string binary data to create a message
     * @return static
     */
    public static function createFromBlob($blob)
    {
        return static::createWithRawBody(
            substr($blob,0, 16),
            unpack('V', $blob, 16),
            substr($blob,20)
        );
    }

    public static function createWithRawBody($uuid, $dNum, $rawBody)
    {
        $message = new static($uuid, $dNum);
        $message->rawBody = $rawBody;
        $message->body = json_decode($message->rawBody, false);

        return $message;
    }

    public function getBlob()
    {
        return $this->uuid . pack('V', $this->dNum) . $this->rawBody;
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }

    public function setBody(\stdClass $body)
    {
        $this->body = $body;
        $this->rawBody = json_encode($this->body, JSON_FORCE_OBJECT);
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }
}