<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 19:47
 */

namespace TriAn\IqoTest\core\db\model;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\managed\BalanceShortage;
use TriAn\IqoTest\core\exception\TransferException;
use TriAn\IqoTest\core\exception\DBException;

/**
 * Class Balance
 * @property int $user
 * @property string $balance
 */
class Balance
{
    /**
     * @param Transaction $transaction
     * @param int $user
     * @return Balance
     */
    protected static function find(Transaction $transaction, $user)
    {
        return $transaction->execute(
            'SELECT * FROM balance WHERE user = :user',
            [':user' => $user]
        )->fetchObject(static::class);
    }

    /**
     * @param Transaction $transaction
     * @param int $user
     * @param string $amount
     * @return Balance
     */
    public static function withdraw(Transaction $transaction, $user, $amount)
    {
        try {
            $transaction->execute(
                'UPDATE balance SET balance = balance - :amount WHERE user = :user',
                [':amount' => $amount, ':user' => $user]
            );
        } catch (DBException $ex) {
            if ($ex->getCode() == 22003) {
                //User has insufficient funds
                throw new BalanceShortage(static::find($transaction, $user));
            }
            throw $ex;
        }
        return static::find($transaction, $user);
    }

    /**
     * @param Transaction $transaction
     * @param int $user
     * @param string $amount
     * @return Balance
     */
    public static function enroll(Transaction $transaction, $user, $amount)
    {
        $transaction->execute(
            'INSERT INTO balance (user, balance) VALUE (:user, :amount)
                    ON DUPLICATE KEY UPDATE balance = balance + :amount',
            [':amount' => $amount, ':user' => $user]
        );
        return static::find($transaction, $user);
    }

    /**
     * @param Transaction $transaction
     * @param int $fromUser
     * @param int $toUser
     * @param string $amount
     * @return Balance[]
     */
    public static function transfer(Transaction $transaction, $fromUser, $toUser, $amount)
    {
        if ($fromUser === $toUser) {
            throw new TransferException('Can\'t transfer money between equal accounts');
        }

        $balances = [];

        /**
         * Prevent a deadlock here
         */
        if ($fromUser < $toUser) {
            $balances[] = static::withdraw($transaction, $fromUser, $amount);
            $balances[] = static::enroll($transaction, $toUser, $amount);
            return $balances;
        }
        $balances[] = static::enroll($transaction, $toUser, $amount);
        $balances[] = static::withdraw($transaction, $fromUser, $amount);
        return $balances;
    }

}