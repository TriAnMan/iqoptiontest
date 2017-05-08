<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:55
 */

namespace TriAn\IqoTest\tests;


use TriAn\IqoTest\core\App;
use TriAn\IqoTest\core\Processor;
use TriAn\IqoTest\tests\stubs\AMQPMessageStub;
use TriAn\IqoTest\tests\stubs\LoggerStub;
use TriAn\IqoTest\tests\stubs\QueueStub;

class FunctionalCase extends \PHPUnit_Framework_TestCase
{
    public static $config = [];

    public static function setUpBeforeClass()
    {
        if (!static::$config) {
            static::$config = require(IQ_TEST_ROOT . '/tests/config.php');
        }
        $app = new App(static::$config);
        $app->run();
    }

    public function setUp()
    {
        QueueStub::clean();
        LoggerStub::clean();
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
}