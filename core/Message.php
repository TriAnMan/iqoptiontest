<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 20:11
 */

namespace TriAn\IqoTest\core;


use TriAn\IqoTest\core\exception\MessageParseException;

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
     * @param string $blob binary data to create a message
     * @return static
     */
    public static function createFromBlob($blob)
    {
        if (strlen($blob) <= 20) {
            throw new MessageParseException('Message has no headers');
        }
        return static::createWithRawBody(
            substr($blob,0, 16),
            unpack('V', substr($blob,16, 4))[1],
            substr($blob,20)
        );
    }

    public static function createWithRawBody($uuid, $dNum, $rawBody)
    {
        $message = new static($uuid, $dNum);
        $message->rawBody = $rawBody;
        $body = json_decode($message->rawBody, false);
        if (is_null($body)) {
            throw new MessageParseException('Can\'t decode message body');
        }
        $message->body = $body;

        return $message;
    }

    public function validate(Validator $validator)
    {
        $validator->validateWithDefaultSchema($this->body);

        if (!$validator->isValid()) {
            $message = "JSON does not validate. Violations:\n";
            foreach ($validator->getErrors() as $error) {
                $message .= sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            throw new MessageParseException($message);
        }
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