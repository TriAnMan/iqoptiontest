<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 18:21
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\model\Operation;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

abstract class Base implements IAction
{
    public function run(Message $request, Transaction $transaction)
    {
        $response = $this->process($request, $transaction);
        Operation::create($transaction, $request, $response);
        return $response;
    }

    abstract public function process(Message $request, Transaction $transaction);
}