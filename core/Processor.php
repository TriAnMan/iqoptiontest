<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:52
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Message\AMQPMessage;
use TriAn\IqoTest\core\action\Error;
use TriAn\IqoTest\core\action\Duplicate;
use TriAn\IqoTest\core\action\IAction;
use TriAn\IqoTest\core\db\Transaction;
use TriAn\IqoTest\core\exception\managed\ReportableException;
use TriAn\IqoTest\core\exception\managed\ProcessedDuplicate;

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
        App::info("Input message: [uuid: " . bin2hex($request->uuid) . ", dNum: {$request->dNum}, body: " . $request->getRawBody() . "]");

        $request->validate(new Validator());

        $transaction = new Transaction(App::$instance->db);

        try {
            $response = $this->processDuplicate($request, $transaction);
        } catch (ProcessedDuplicate $ex) {
            App::warn('Stop propagation of an already processed duplicate message ' . bin2hex($request->uuid));
            $transaction->rollBack();
            $this->processAck();
            return;
        }

        if (!$response) {
            try {
                $response = $this->processRequest($request, $transaction);
            } catch (ReportableException $exception) {
                $transaction->rollBack();
                $transaction = new Transaction(App::$instance->db);
                $response = $this->processError($request, $transaction, $exception);
            }
        }

        $transaction->commit();
        $transaction = null;

        $this->sendResponse($response);
    }

    protected function sendResponse(Message $response)
    {
        App::info("Output message: [uuid: " . bin2hex($response->uuid) . ", dNum: {$response->dNum}, body: " . $response->getRawBody() . "]");
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

    protected function processError(Message $request, Transaction $transaction, ReportableException $exception)
    {
        return (new Error())->setException($exception)->run($request, $transaction);
    }
}