<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 18:24
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\managed\ReportableException;
use TriAn\IqoTest\core\Message;

class Error extends Base
{
    /**
     * @var ReportableException
     */
    protected $exception;

    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return Message
     */
    public function process(Message $request, Transaction $transaction)
    {
        $response = $this->exception->generateResponse($request);
        return (new Message($request->uuid, 0))->setBody($response);
    }

    public function setException(ReportableException $exception)
    {
        $this->exception = $exception;
        return $this;
    }
}