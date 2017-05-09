<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:55
 */

namespace TriAn\IqoTest\tests;


use TriAn\IqoTest\core\App;
use TriAn\IqoTest\core\Message;
use TriAn\IqoTest\core\Processor;
use TriAn\IqoTest\tests\stubs\AMQPMessageStub;
use TriAn\IqoTest\tests\stubs\LoggerStub;
use TriAn\IqoTest\tests\stubs\QueueStub;

class FunctionalCase extends \PHPUnit_Framework_TestCase
{
    public static $config = [];

    /**
     * @var App
     */
    public static $app;

    public static function setUpBeforeClass()
    {
        if (!static::$config) {
            static::$config = require(IQ_TEST_ROOT . '/tests/config.php');
        }
        static::$app = new App(static::$config);
        static::$app->run();
    }

    public function setUp()
    {
        QueueStub::clean();
        LoggerStub::clean();
    }

    public function tearDown()
    {
        if (static::$app->db->inTransaction()) {
            static::$app->db->rollBack();
        }
    }

    protected function sendMessage($blob)
    {
        $processor = new Processor((new AMQPMessageStub($blob))->addDeliveryInfo());
        $processor->processInput();
    }

    protected function getLastResponse()
    {
        return QueueStub::getLastResponse();
    }

    protected function getLastLog()
    {
        return LoggerStub::getLastMessage();
    }

    protected function getResponseStack()
    {
        return QueueStub::getResponseStack();
    }

    protected function assertLog($logLevel, $logMessage, $message)
    {
        list($lvl, $msg) = $this->getLastLog();
        $this->assertEquals($logLevel, $lvl, 'Got expected log level about ' . $message);
        $this->assertEquals($logMessage, $msg, 'Got expected log message about ' . $message);
    }

    protected function assertExpectedResponse(Message $expectedResponse)
    {
        $actualResponse = Message::createFromBlob($this->getLastResponse());
        $this->assertEquals($expectedResponse->uuid, $actualResponse->uuid, 'Got expected message uid');
        $this->assertEquals($expectedResponse->dNum, $actualResponse->dNum, 'Got expected message dNum');
        $this->assertJsonStringEqualsJsonString(
            $expectedResponse->getRawBody(),
            $actualResponse->getRawBody(),
            'Got expected message body'
        );
    }

    /**
     * @param $uuid
     * @param $dNum
     * @param array $body
     * @param array $bodyOverride
     * @return Message
     */
    protected function createMessage($uuid, $dNum, array $body, array $bodyOverride)
    {
        if (array_key_exists('action', $bodyOverride)) {
            return (new Message($uuid, $dNum))->setBody((object)$bodyOverride);
        }
        return (new Message($uuid, $dNum))->setBody((object)array_merge($body, $bodyOverride));
    }

    protected static $requestBody;
    protected static $responseBody;

    protected function createRequest($uuid, $dNum, array $bodyOverride = [])
    {
        return $this->createMessage($uuid, $dNum, static::$requestBody, $bodyOverride);
    }

    protected function createResponse($uuid, $dNum, array $bodyOverride = [])
    {
        return $this->createMessage($uuid, $dNum, static::$responseBody, $bodyOverride);
    }

    protected function createResponseError($uuid, $dNum, array $bodyOverride = [])
    {
        return $this->createMessage($uuid, $dNum, static::$requestBody, $bodyOverride);
    }

}