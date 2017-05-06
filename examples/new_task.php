<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->set_ack_handler(
    function (AMQPMessage $message) {
        echo "Message acked with content " . $message->body . PHP_EOL;
    }
);
$channel->set_nack_handler(
    function (AMQPMessage $message) {
        echo "Message nacked with content " . $message->body . PHP_EOL;
    }
);
$channel->set_return_listener(
    function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
        echo "($replyCode, $replyText, $exchange, $routingKey) Message returned with content " . $message->body . PHP_EOL;
    }
);

$channel->confirm_select();

$channel->queue_declare('task_queue', false, true, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $body = new \stdClass();
    $body->action = 'enroll';
    $body->user = 1;
    $body->amount = '12.12';
    $data = "1234567890123456" . pack('V', 0) . json_encode($body, JSON_FORCE_OBJECT);
} else {
    $uuid = $argv[1];
    $dNum = pack('V', intval($argv[2]));
    $body = $argv[3];
    $data = $uuid . $dNum . $body;
}

$msg = new AMQPMessage($data,
    ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
);

$channel->basic_publish($msg, '', 'task_queue', true);

echo " [x] Sent ", $data, "\n";

$channel->wait_for_pending_acks_returns();

$channel->close();
$connection->close();