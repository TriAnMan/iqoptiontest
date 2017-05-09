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
 * @property string $operation_uuid
 * @property int $user
 * @property string $amount
 */
class Lock
{
    /**
     * Dirty hack!
     * Used only for \PDO::fetchObject()
     * @see http://php.net/manual/en/pdostatement.fetchobject.php
     */
    public function __construct()
    {
        if (isset($this->user)) {
            $this->user = intval($this->user);
        }
    }

    /**
     * @param Transaction $transaction
     * @param string $operationUuid
     * @return Lock
     */
    protected static function findForUpdate(Transaction $transaction, $operationUuid)
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
     * @return array [Balance, Operation]
     */
    public static function redeem(Transaction $transaction, $operationUuid)
    {
        $lock = static::findForUpdate($transaction, $operationUuid);
        $deleted = $transaction->execute(
            'DELETE FROM `lock` WHERE operation_uuid = :operation_uuid',
            [
                ':operation_uuid' => $lock->operation_uuid,
            ]
        )->rowCount();
        if ($deleted !== 1) {
            throw new LockNotFound();
        }
        $balance = Balance::find($transaction, $lock->user);
        return [$balance, $lock];
    }

    /**
     * @param Transaction $transaction
     * @param string $operationUuid
     * @return array [Balance, Operation]
     */
    public static function cancel(Transaction $transaction, $operationUuid)
    {
        $lock = static::findForUpdate($transaction, $operationUuid);
        $deleted = $transaction->execute(
            'DELETE FROM `lock` WHERE operation_uuid = :operation_uuid',
            [
                ':operation_uuid' => $lock->operation_uuid,
            ]
        )->rowCount();
        if ($deleted !== 1) {
            throw new LockNotFound();
        }
        $balance = Balance::enroll($transaction, $lock->user, $lock->amount);
        return [$balance, $lock];
    }
}