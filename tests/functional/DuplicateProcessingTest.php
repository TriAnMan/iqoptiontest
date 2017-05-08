<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\exception\MessageHashMismatch;
use TriAn\IqoTest\core\Message;
use TriAn\IqoTest\tests\FunctionalCase;
use TriAn\IqoTest\tests\stubs\LoggerStub;

class DuplicateProcessingTest extends FunctionalCase
{
    private static $uuid;
    private static $requestBody;
    private static $responseBody;

    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'enroll', 'user' => 1, 'amount' => '12.00'];
        static::$responseBody = static::$requestBody + ['balance' => '12.00', 'result' => 'ok'];
        static::$uuid = openssl_random_pseudo_bytes(16);

        parent::setUpBeforeClass();
    }

    public function testSendMessage()
    {
        $request = (new Message(static::$uuid, 0))->setBody((object)static::$requestBody);
        $response = (new Message(static::$uuid, 0))->setBody((object)static::$responseBody);

        $this->sendMessage($request->getBlob());

        $this->assertEquals($response->getBlob(), $this->getLastResponse(), 'Got expected response');
    }

    /**
     * @depends testSendMessage
     */
    public function testCheckAllowedDuplicateProcessing()
    {
        $request = (new Message(static::$uuid, 0))->setBody((object)static::$requestBody);
        $response = (new Message(static::$uuid, 1))->setBody((object)static::$responseBody);

        $this->sendMessage($request->getBlob());

        $this->assertEquals($response->getBlob(), $this->getLastResponse(), 'Got expected duplicate response');
    }

    /**
     * @depends testSendMessage
     */
    public function testCheckProhibitedDuplicateProcessing()
    {
        $request = (new Message(static::$uuid, 1))->setBody((object)static::$requestBody);
        $responseStackLen = count($this->getResponseStack());

        $this->sendMessage($request->getBlob());
        
        $this->assertLog(
            LoggerStub::LEVEL_WARN,
            'Stop propagation of an already processed duplicate message ' . bin2hex($request->uuid),
            'Got warning message'
        );
        $this->assertEquals(
            $responseStackLen,
            count($this->getResponseStack()),
            'No new messages was actually sent'
        );
    }

    /**
     * @depends testSendMessage
     */
    public function testFailOnDuplicateBodyMissMatch()
    {
        $request = (new Message(static::$uuid, 0))->setBody((object)(static::$requestBody + ['other'=>'field']));

        $this->expectException(MessageHashMismatch::class);
        $this->expectExceptionMessage('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testSendMessage
     */
    public function testFailOnDuplicateBodyMissMatchWithDifferentDNum()
    {
        $request = (new Message(static::$uuid, 1))->setBody((object)(static::$requestBody + ['other'=>'field']));

        $this->expectException(MessageHashMismatch::class);
        $this->expectExceptionMessage('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');

        $this->sendMessage($request->getBlob());
    }

}