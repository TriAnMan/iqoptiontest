<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:54
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

interface IAction
{
    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return Message
     */
    public function run(Message $request, Transaction $transaction);
}