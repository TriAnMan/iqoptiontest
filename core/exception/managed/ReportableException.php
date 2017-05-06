<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 19:55
 */

namespace TriAn\IqoTest\core\exception\managed;


use TriAn\IqoTest\core\Message;

abstract class ReportableException extends \UnexpectedValueException
{
    /**
     * @param Message $request
     * @return Message response
     */
    abstract public function generateResponse(Message $request);
}