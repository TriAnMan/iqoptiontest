<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 16:35
 */

namespace TriAn\IqoTest\tests\stubs;


use PhpAmqpLib\Channel\AMQPChannel;

class AMQPChannelStub extends AMQPChannel
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }

    public function basic_ack($delivery_tag, $multiple = false)
    {
    }
}