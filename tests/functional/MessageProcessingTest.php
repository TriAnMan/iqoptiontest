<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\exception\MessageHashMismatch;
use TriAn\IqoTest\core\exception\MessageParseException;
use TriAn\IqoTest\core\exception\MessageValidationException;
use TriAn\IqoTest\tests\FunctionalCase;
use TriAn\IqoTest\tests\stubs\LoggerStub;

class MessageProcessingTest extends FunctionalCase
{
    protected static $uuid;

    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'enroll', 'user' => 1, 'amount' => '12.00'];
        static::$responseBody = static::$requestBody + ['balance' => '12.00', 'result' => 'ok'];
        static::$uuid = openssl_random_pseudo_bytes(16);

        parent::setUpBeforeClass();
    }

    public function testSendMessage()
    {
        $request = $this->createRequest(static::$uuid, 0);
        $response = $this->createResponse(static::$uuid, 0);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    public function testMessageParsing()
    {
        $uuid = openssl_random_pseudo_bytes(15);
        $request = $this->createRequest($uuid, 0);

        $this->expectException(MessageParseException::class);

        $this->sendMessage($request->getBlob());
    }

    public function testMessageValidation()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '1000000000.00']);

        $this->expectException(MessageValidationException::class);

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testSendMessage
     * @depends testMessageParsing
     * @depends testMessageValidation
     */
    public function testNothingIsChangedAfterFails()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, ['amount' => '0.00']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testSendMessage
     */
    public function testCheckAllowedDuplicateProcessing()
    {
        $request = $this->createRequest(static::$uuid, 0);
        $response = $this->createResponse(static::$uuid, 1);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testSendMessage
     */
    public function testCheckProhibitedDuplicateProcessing()
    {
        $request = $this->createRequest(static::$uuid, 1);
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
        $request = $this->createRequest(static::$uuid, 0, ['amount' => '13.00']);

        $this->expectException(MessageHashMismatch::class);
        $this->expectExceptionMessage('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testSendMessage
     */
    public function testFailOnDuplicateBodyMissMatchWithDifferentDNum()
    {
        $request = $this->createRequest(static::$uuid, 1, ['amount' => '13.00']);

        $this->expectException(MessageHashMismatch::class);
        $this->expectExceptionMessage('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');

        $this->sendMessage($request->getBlob());
    }

}
