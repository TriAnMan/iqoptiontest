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

class RedeemLockActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'redeemLock', 'operationUuid' => '00000000000000000000000000000000'];
        static::$responseBody = static::$requestBody + ['user' => 50, 'balance' => '988.00', 'amount' => '12.00', 'result' => 'ok'];

        parent::setUpBeforeClass();
    }

    public function testRedeemAbsentLock()
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

    public function testBasicRedeem()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 50, 'amount' => '1000.00']);
        $this->sendMessage($request->getBlob());
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'createLock', 'user' => 50, 'amount' => '12.00']);
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
     * @depends testBasicRedeem
     * @param string $operationUuid
     */
    public function testRedeemMore($operationUuid)
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
     * @depends testRedeemMore
     */
    public function testCanCreateLockAndBalanceWasNotChanged()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, [
            'action' => 'createLock',
            'user' => 50,
            'amount' => '12.00',
        ]);
        $response = $this->createResponse($uuid, 0, [
            'action' => 'createLock',
            'user' => 50,
            'amount' => '12.00',
            'result' => 'ok',
            'balance' => '976.00',
            'operationUuid' => bin2hex($uuid),
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }
}
