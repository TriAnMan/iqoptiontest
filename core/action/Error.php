<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 18:24
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\Message;

class Error extends Base
{
    /**
     * @param Message $response
     * @param Transaction $transaction
     * @return Message
     */
    public function process(Message $response, Transaction $transaction)
    {
        return (new Message($response->uuid, 0))->setBody($response->getBody());
    }
}