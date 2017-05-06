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
use TriAn\IqoTest\core\exception\MessageHashMismatch;
use TriAn\IqoTest\core\exception\managed\ProcessedDuplicate;
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
        $duplicate = $this->checkDuplicate($request, $transaction);

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

    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return Operation
     */
    protected function checkDuplicate(Message $request, Transaction $transaction)
    {
        $duplicate = Operation::find($transaction, $request->uuid);

        if (!$duplicate) {
            return null;
        }

        if (md5($request->getRawBody(), true) !== $duplicate->input_md5) {
            throw new MessageHashMismatch('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');
        }

        if ($request->dNum !== $duplicate->input_dup_num) {
            throw new ProcessedDuplicate();
        }

        return $duplicate;
    }
}