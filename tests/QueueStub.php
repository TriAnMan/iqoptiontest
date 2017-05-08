<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:26
 */

namespace TriAn\IqoTest\tests;


use TriAn\IqoTest\core\Queue;

class QueueStub extends Queue
{
    protected static $result;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($config)
    {
    }

    public function run()
    {
    }

    public function send($blob)
    {
        static::$result = $blob;
    }

    public function init(callable $inputCallback, callable $ackCallback)
    {
    }

    public static function getResult()
    {
        return static::$result;
    }
}