<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:56
 */

namespace TriAn\IqoTest\core\action\message;


use TriAn\IqoTest\core\action\Base;
use TriAn\IqoTest\core\db\dao\Balance;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

class Enroll extends Base
{
    public function process(Message $request, Transaction $transaction)
    {
        $action = $request->getBody();
        $action->balance = Balance::enroll($transaction, $action->user, $action->amount)->balance;
        $action->result = 'ok';
        return (new Message($request->uuid, 0))->setBody($action);
    }
}