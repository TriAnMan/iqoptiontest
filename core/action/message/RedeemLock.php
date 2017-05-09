<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:58
 */

namespace TriAn\IqoTest\core\action\message;


use TriAn\IqoTest\core\action\Base;
use TriAn\IqoTest\core\db\model\Lock;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

class RedeemLock extends Base
{
    public function process(Message $request, Transaction $transaction)
    {
        $action = $request->getBody();
        list($balance, $operation) = Lock::redeem($transaction, hex2bin($action->operationUuid));
        $action->user = $balance->user;
        $action->balance = $balance->balance;
        $action->amount = $operation->amount;
        $action->result = 'ok';
        return (new Message($request->uuid, 0))->setBody($action);
    }
}