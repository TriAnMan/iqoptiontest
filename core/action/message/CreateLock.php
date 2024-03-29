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

class CreateLock extends Base
{
    public function process(Message $request, Transaction $transaction)
    {
        $action = $request->getBody();
        $action->balance = Lock::create($transaction, $request->uuid, $action->user, $action->amount)->balance;
        $action->operationUuid = bin2hex($request->uuid);
        $action->result = 'ok';
        return (new Message($request->uuid, 0))->setBody($action);
    }
}