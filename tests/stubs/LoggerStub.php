<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:57
 */

namespace TriAn\IqoTest\tests\stubs;


use TriAn\IqoTest\core\Logger;

class LoggerStub extends Logger
{
    protected static $messagesStack = [];

    protected static function log($level, $message)
    {
        static::$messagesStack[] = [$level, $message];
    }

    public static function getLastMessage()
    {
        return static::$messagesStack[count(static::$messagesStack) - 1];
    }

    public static function getMessageStack()
    {
        return static::$messagesStack;
    }

    public static function clean()
    {
        static::$messagesStack = [];
    }
}