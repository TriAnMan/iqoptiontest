<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 16:31
 */

namespace TriAn\IqoTest\tests\stubs;


use PhpAmqpLib\Message\AMQPMessage;

class AMQPMessageStub extends AMQPMessage
{
    public function addDeliveryInfo()
    {
        $this->delivery_info['delivery_tag'] = openssl_random_pseudo_bytes(8);
        $this->delivery_info['channel'] = new AMQPChannelStub();
        return $this;
    }
}