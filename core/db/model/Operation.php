<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 18:51
 */

namespace TriAn\IqoTest\core\db\model;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\managed\ProcessedDuplicate;
use TriAn\IqoTest\core\exception\MessageHashMismatch;
use TriAn\IqoTest\core\Message;

/**
 * Class Operation
 * @property string $uuid message id
 * @property int $input_dup_num duplicate number of input message
 * @property string $input_md5 hash of an input message body
 * @property int $output_dup_num number of duplicate messages sent
 * @property string $completed operation completion DateTime
 * @property string $raw_body
 *
 */
class Operation
{
    /**
     * Dirty hack!
     * Used only for \PDO::fetchObject()
     * @see http://php.net/manual/en/pdostatement.fetchobject.php
     */
    public function __construct()
    {
        if (isset($this->input_dup_num)) {
            $this->input_dup_num = intval($this->input_dup_num);
        }
        if (isset($this->output_dup_num)) {
            $this->output_dup_num = intval($this->output_dup_num);
        }
    }

    /**
     * @param Transaction $transaction
     * @param string $uuid
     * @return Operation
     */
    protected static function findForUpdate(Transaction $transaction, $uuid)
    {
        return $transaction->execute(
            'SELECT * FROM operation WHERE uuid = :uuid FOR UPDATE',
            [':uuid' => $uuid]
        )->fetchObject(static::class);
    }

    public static function updateDuplicate(Transaction $transaction, $uuid)
    {
        $transaction->execute(
            'UPDATE operation SET output_dup_num = output_dup_num + 1 WHERE uuid = :uuid',
            [':uuid' => $uuid]
        );
    }

    public static function create(Transaction $transaction, Message $request, Message $response)
    {
        $transaction->execute(
            'INSERT INTO operation 
                (uuid, input_dup_num, input_md5, output_dup_num, completed, raw_body) 
                VALUE 
                (:uuid, :input_dup_num, :input_md5, :output_dup_num, UTC_TIMESTAMP(), :raw_body)
            ',
            [
                ':uuid' => $response->uuid,
                ':input_dup_num' => $request->dNum,
                ':input_md5' => md5($request->getRawBody(), true),
                ':output_dup_num' => $response->dNum,
                ':raw_body' => $response->getRawBody(),
            ]
        );
    }

    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return Operation
     */
    public static function checkDuplicate(Message $request, Transaction $transaction)
    {
        $duplicate = Operation::findForUpdate($transaction, $request->uuid);

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