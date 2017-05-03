<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 0:46
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Connection\AMQPStreamConnection;

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

    public function init(callable $callback)
    {
        $connection = new AMQPStreamConnection(...$this->config['connection']);
        $this->channel = $connection->channel();
        $this->channel->queue_declare($this->config['inbound_queue'], false, true, false, false);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            $this->config['inbound_queue'],
            '',
            false,
            false,
            false,
            false,
            $callback
        );
    }

    public function run()
    {
        while(true) {
            $this->channel->wait();
        }
    }
}