<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:57
 */

namespace TriAn\IqoTest\tests;


use TriAn\IqoTest\core\Logger;

class LoggerStub extends Logger
{
    protected static $log = [];

    protected static function log($level, $message)
    {
        static::$log[] = [$level, $message];
    }

    public static function getLastMessage()
    {
        return static::$log[count(static::$log) - 1];
    }
}