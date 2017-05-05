<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 23:17
 */

namespace TriAn\IqoTest\core\db\model;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\managed\AbsentLock;
use TriAn\IqoTest\core\exception\LockNotFound;

/**
 * Class Lock
 * @property int $operation_uuid
 * @property int $user
 * @property string $amount
 */
class Lock
{
    /**
     * @param Transaction $transaction
     * @param string $operationUuid
     * @return Lock
     */
    protected static function find(Transaction $transaction, $operationUuid)
    {
        $lock = $transaction->execute(
            'SELECT * FROM `lock` WHERE operation_uuid = :operation_uuid FOR UPDATE',
            [':operation_uuid' => $operationUuid]
        )->fetchObject(static::class);

        if (!$lock) {
            throw new AbsentLock();
        }
        return $lock;
    }


    /**
     * @param Transaction $transaction
     * @param string $uuid
     * @param int $user
     * @param string $amount
     * @return Balance new user balance
     */
    public static function create(Transaction $transaction, $uuid, $user, $amount)
    {
        $transaction->execute(
            'INSERT INTO `lock` 
                (operation_uuid, user, amount) 
                VALUE 
                (:operation_uuid, :user, :amount)
            ',
            [
                ':operation_uuid' => $uuid,
                ':user' => $user,
                ':amount' => $amount,
            ]
        );
        $balance = Balance::withdraw($transaction, $user, $amount);
        return $balance;
    }

    /**
     * @param Transaction $transaction
     * @param string $operationUuid
     * @return Lock
     */
    public static function redeem(Transaction $transaction, $operationUuid)
    {
        $operation = static::find($transaction, $operationUuid);
        $deleted = $transaction->execute(
            'DELETE FROM `lock` WHERE operation_uuid = :operation_uuid',
            [
                ':operation_uuid' => $operation->operation_uuid,
            ]
        )->rowCount();
        if ($deleted !== 1) {
            throw new LockNotFound();
        }
        return $operation;
    }

    /**
     * @param Transaction $transaction
     * @param string $operationUuid
     * @return array [Balance, Operation]
     */
    public static function cancel(Transaction $transaction, $operationUuid)
    {
        $operation = static::find($transaction, $operationUuid);
        $deleted = $transaction->execute(
            'DELETE FROM `lock` WHERE operation_uuid = :operation_uuid',
            [
                ':operation_uuid' => $operation->operation_uuid,
            ]
        )->rowCount();
        if ($deleted !== 1) {
            throw new LockNotFound();
        }
        $balance = Balance::enroll($transaction, $operation->user, $operation->amount);
        return [$balance, $operation];
    }
}