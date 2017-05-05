<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:52
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Message\AMQPMessage;
use TriAn\IqoTest\core\action\BalanceError;
use TriAn\IqoTest\core\action\Duplicate;
use TriAn\IqoTest\core\action\IAction;
use TriAn\IqoTest\core\db\dao\Balance;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\BalanceShortage;

class Processor
{
    /**
     * @var AMQPMessage
     */
    protected $inputAmqp;

    public function __construct(AMQPMessage $inputAmqp)
    {
        $this->inputAmqp = $inputAmqp;
    }

    public function processInput()
    {
        $request = Message::createFromBlob($this->inputAmqp->getBody());
        $transaction = new Transaction(App::$instance->db);

        $response = $this->processDuplicate($request, $transaction);
        if (!$response) {
            try {
                $response = $this->processRequest($request, $transaction);
            } catch (BalanceShortage $ex) {
                $transaction->rollBack();
                $response = $this->processBalanceError($request, $transaction, $ex->getBalance());
            }
        }

        $transaction->commit();
        $transaction = null;

        $this->sendResponse($response);
    }

    protected function sendResponse(Message $response)
    {
        App::$instance->queue->send($response->getBlob());
    }

    public function processAck()
    {
        $this->inputAmqp->delivery_info['channel']->basic_ack($this->inputAmqp->delivery_info['delivery_tag']);
        $this->inputAmqp = null;
    }

    protected function processRequest(Message $request, Transaction $transaction)
    {
        $actionStr = ucfirst($request->getBody()->action);
        /** @var IAction $action */
        $action = App::createObject('TriAn\\IqoTest\\core\\action\\message\\' . $actionStr , []);
        return $action->run($request, $transaction);
    }

    protected function processDuplicate(Message $request, Transaction $transaction)
    {
        return (new Duplicate())->run($request, $transaction);
    }

    /**
     * Hack to handle error messages
     * @todo refactor this thing
     * @param Message $request
     * @param Transaction $transaction
     * @param Balance $balance
     * @return Message
     */
    protected function processBalanceError(Message $request, Transaction $transaction, Balance $balance)
    {
        $response = $request->getBody();
        $response->balances = [$balance];
        $response->result = 'error';
        return (new BalanceError())->run($response, $transaction);
    }
}