<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 20:18
 */

namespace TriAn\IqoTest\core\exception;

use TriAn\IqoTest\core\db\dao\Balance;

class BalanceShortage extends \UnexpectedValueException
{
    protected $balance;

    public function __construct(Balance $balance)
    {
        $this->balance = $balance;
        parent::__construct();
    }

    public function getBalance()
    {
        return $this->balance;
    }
}