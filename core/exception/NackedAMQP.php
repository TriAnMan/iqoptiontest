<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 13:53
 */

namespace TriAn\IqoTest\core\exception;


use PhpAmqpLib\Message\AMQPMessage;

class NackedAMQP extends \RuntimeException
{
    public function __construct(AMQPMessage $amqp)
    {
        $amqp = "messageBody: {$amqp->body}";
        parent::__construct($amqp, 0, null);
    }
}