<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:52
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Message\AMQPMessage;
use TriAn\IqoTest\core\action\Duplicate;
use TriAn\IqoTest\core\action\IAction;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\MessageHashMismatch;
use TriAn\IqoTest\core\exception\ProcessedDuplicate;

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
            $response = $this->processRequest($request, $transaction);
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
}