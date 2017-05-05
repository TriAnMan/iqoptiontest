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

class BalanceShortage extends HandledException
{
    protected $balance;

    public function __construct(Balance $balance)
    {
        $this->balance = $balance;
        parent::__construct();
    }

    /**
     * @param Message $request
     * @return Message response
     */
    public function generateResponse(Message $request)
    {
        $response = $request->getBody();
        $response->balances = [$this->balance];
        $response->error = 'insufficient_funds';
        return (new Message($request->uuid, 0))->setBody($response);
    }
}