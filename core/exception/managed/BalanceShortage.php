<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 04.05.2017
 * Time: 20:18
 */

namespace TriAn\IqoTest\core\exception\managed;

use TriAn\IqoTest\core\db\model\Balance;
use TriAn\IqoTest\core\Message;

class BalanceShortage extends ReportableException
{
    /**
     * @var Balance[]
     */
    protected $balances = [];

    /**
     * @param Balance[] $balances
     */
    public function __construct(array $balances)
    {
        $this->balances = $balances;
        parent::__construct();
    }

    public function appendBalance(Balance $balance)
    {
        if (!in_array($balance, $this->balances)) {
            $this->balances[] = $balance;
        }
        return $this;
    }

    /**
     * @param Message $request
     * @return \stdClass response
     */
    public function generateResponse(Message $request)
    {
        $response = $request->getBody();
        $response->balances = $this->balances;
        $response->error = 'insufficient_funds';
        return $response;
    }
}