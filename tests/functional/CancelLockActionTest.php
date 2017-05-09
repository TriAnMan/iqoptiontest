<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\Message;
use TriAn\IqoTest\tests\FunctionalCase;

class CancelLockActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'cancelLock', 'operationUuid' => '00000000000000000000000000000000'];
        static::$responseBody = static::$requestBody + ['user' => 60, 'balance' => '1000.00', 'amount' => '12.00', 'result' => 'ok'];

        parent::setUpBeforeClass();
    }

    public function testCancelAbsentLock()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'error' => 'absent_lock',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    public function testBasicCancel()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 60, 'amount' => '1000.00']);
        $this->sendMessage($request->getBlob());
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'createLock', 'user' => 60, 'amount' => '12.00']);
        $this->sendMessage($request->getBlob());
        $operationUuid = Message::createFromBlob($this->getLastResponse())->getBody()->operationUuid;


        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['operationUuid' => $operationUuid]);
        $response = $this->createResponse($uuid, 0, ['operationUuid' => $operationUuid]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);

        return $operationUuid;
    }

    /**
     * @depends testBasicCancel
     * @param string $operationUuid
     */
    public function testCancelMore($operationUuid)
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['operationUuid' => $operationUuid]);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'operationUuid' => $operationUuid,
                'error' => 'absent_lock',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testCancelMore
     */
    public function testCanCreateLockAndBalanceWasNotChanged()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, [
            'action' => 'createLock',
            'user' => 60,
            'amount' => '12.00',
        ]);
        $response = $this->createResponse($uuid, 0, [
            'action' => 'createLock',
            'user' => 60,
            'amount' => '12.00',
            'result' => 'ok',
            'balance' => '988.00',
            'operationUuid' => bin2hex($uuid),
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }
}
