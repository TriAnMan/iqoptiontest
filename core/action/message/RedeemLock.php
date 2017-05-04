<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:58
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\dao\Lock;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

class RedeemLock extends Base
{
    public function process(Message $request, Transaction $transaction)
    {
        $action = $request->getBody();
        $action->amount = Lock::redeem($transaction, $action->operationUuid)->amount;
        $action->result = 'ok';
        return (new Message($request->uuid, 0))->setBody($action);
    }
}