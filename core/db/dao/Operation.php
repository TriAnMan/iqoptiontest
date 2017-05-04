<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 18:51
 */

namespace TriAn\IqoTest\core\db\dao;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

/**
 * Class Operation
 * @property string $uuid message id
 * @property int $input_dup_num duplicate number of input message
 * @property int $output_dup_num number of duplicate messages sent
 * @property string $completed operation completion DateTime
 * @property string $raw_body
 *
 */
class Operation
{
    /**
     * @param Transaction $transaction
     * @param $uuid string
     * @return static
     */
    public static function find(Transaction $transaction, $uuid)
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
            <<<SQL
INSERT INTO operation 
(uuid, input_dup_num, output_dup_num, completed, raw_body) 
VALUE 
(:uuid, :input_dup_num, 0, UTC_TIMESTAMP(), :raw_body)
SQL
            ,
            [
                ':uuid' => $response->uuid,
                ':input_dup_num' => $request->dNum,
                ':raw_body' => $response->getRawBody(),
            ]
        );
    }
}