<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 18:02
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\model\Operation;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

class Duplicate implements IAction
{
    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return Message
     */
    public function run(Message $request, Transaction $transaction)
    {
        $duplicate = Operation::checkDuplicate($request, $transaction);

        if (!$duplicate) {
            return null;
        }

        Operation::updateDuplicate($transaction, $duplicate->uuid);

        return Message::createWithRawBody(
            $duplicate->uuid,
            $duplicate->output_dup_num + 1,
            $duplicate->raw_body
        );
    }

}