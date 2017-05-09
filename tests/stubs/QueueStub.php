<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:26
 */

namespace TriAn\IqoTest\tests\stubs;


use TriAn\IqoTest\core\Queue;

class QueueStub extends Queue
{
    /**
     * @var string[]
     */
    protected static $responseStack = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($config)
    {
    }

    public function run()
    {
    }

    public function send($blob)
    {
        static::$responseStack[] = $blob;
    }

    public function init(callable $inputCallback, callable $ackCallback)
    {
    }

    public static function getResponseStack()
    {
        return static::$responseStack;
    }

    public static function getLastResponse()
    {
        return static::$responseStack[count(static::$responseStack) - 1];
    }

    public static function clean()
    {
        static::$responseStack = null;
    }
}