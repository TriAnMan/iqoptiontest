<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:52
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Message\AMQPMessage;
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
        $action = App::createObject('TriAn\\IqoTest\\core\\action\\' . $actionStr , [$request, $transaction]);
        return $action->run();
    }

    protected function processDuplicate(Message $request, Transaction $transaction)
    {
        try {
            $duplicate = $this->checkDuplicate($request, $transaction);
        } catch (ProcessedDuplicate $ex) {
            App::warn('Stop propagation of an already processed duplicate message' . bin2hex($request->uuid));
            return null;
        }

        $transaction->execute(
            'UPDATE operation SET output_dup_num = output_dup_num + 1 WHERE uuid = :uuid',
            [':uuid' => $duplicate->uuid]
        );
        return Message::createWithRawBody(
            $duplicate->uuid,
            $duplicate->output_dup_num + 1,
            $duplicate->responce_body
        );
    }

    /**
     * @param Message $request
     * @param Transaction $transaction
     * @return \stdClass
     */
    public function checkDuplicate(Message $request, Transaction $transaction)
    {
        $duplicate = $transaction->execute(
            'SELECT * FROM operation WHERE uuid = :uuid FOR UPDATE',
            [':uuid' => $request->uuid]
        )->fetchObject();

        if (!$duplicate) {
            return null;
        }

        if (md5($request->getRawBody()) !== md5($duplicate->raw_body)) {
            throw new MessageHashMismatch('Message ' . bin2hex($request->uuid) . ' duplicate has a different body');
        }

        if ($request->dNum !== $duplicate->input_dup_num) {
            throw new ProcessedDuplicate();
        }

        return $duplicate;
    }
}