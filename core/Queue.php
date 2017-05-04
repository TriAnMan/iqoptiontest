<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 0:46
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use TriAn\IqoTest\core\exception\NackedAMQP;
use TriAn\IqoTest\core\exception\ReturnedAMQP;

class Queue
{
    private $config;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function init(callable $inputCallback, callable $ackCallback)
    {
        $connection = new AMQPStreamConnection(...$this->config['connection']);
        $this->channel = $connection->channel();

        $this->channel->set_ack_handler($ackCallback);
        $this->channel->set_nack_handler(
            function (AMQPMessage $amqp) {
                throw new NackedAMQP($amqp);
            }
        );
        $this->channel->set_return_listener(
            function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $amqp) {
                throw new ReturnedAMQP($replyCode, $replyText, $exchange, $routingKey, $amqp);
            }
        );

        $this->channel->confirm_select();
        $this->channel->queue_declare($this->config['inbound_queue'], false, true, false, false);
        $this->channel->queue_declare($this->config['outbound_queue'], false, true, false, false);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            $this->config['inbound_queue'],
            '',
            false,
            false,
            false,
            false,
            $inputCallback
        );
    }

    public function send($blob)
    {
        $this->channel->basic_publish($blob, '', $this->config['outbound_queue'], true);
        $this->channel->wait_for_pending_acks_returns();
    }

    public function run()
    {
        while(true) {
            $this->channel->wait();
        }
    }
}