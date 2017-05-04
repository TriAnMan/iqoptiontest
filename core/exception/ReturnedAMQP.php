<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 13:59
 */

namespace TriAn\IqoTest\core\exception;


use PhpAmqpLib\Message\AMQPMessage;

class ReturnedAMQP extends \RuntimeException
{
    public function __construct($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $amqp)
    {
        $amqp = "replyCode: {$replyCode}, replyText: {$replyText}, exchange: {$exchange}, "
            . "routingKey: {$routingKey}, messageBody: {$amqp->body}";
        parent::__construct($amqp, 0, null);
    }
}