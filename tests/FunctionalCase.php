<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:55
 */

namespace TriAn\IqoTest\tests;


use PhpAmqpLib\Message\AMQPMessage;
use TriAn\IqoTest\core\App;
use TriAn\IqoTest\core\Processor;

class FunctionalCase extends \PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        $config = require(IQ_TEST_ROOT . '/tests/config.php');
        $app = new App($config);
        $app->run();
    }

    protected function sendMessage($blob)
    {
        $processor = new Processor(new AMQPMessage($blob));
        $processor->processInput();
    }

    protected function getResponse()
    {
        return QueueStub::getResult();
    }

    protected function getLastLog()
    {
        return LoggerStub::getLastMessage();
    }

    protected function assertLog($logLevel, $logMessage, $message)
    {
        list($lvl, $msg) = $this->getLastLog();
        $this->assertEquals($logLevel, $lvl, 'Got expected log level about ' . $message);
        $this->assertEquals($logMessage, $msg, 'Got expected log message about ' . $message);
    }
}