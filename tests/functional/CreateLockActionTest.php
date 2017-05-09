<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\tests\FunctionalCase;

class CreateLockActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'createLock', 'user' => 40, 'amount' => '12.00'];
        static::$responseBody = static::$requestBody + ['balance' => '988.00', 'result' => 'ok'];

        parent::setUpBeforeClass();
    }

    public function testAbsentUserLock()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['user' => 41]);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'user' => 41,
                'absent_users' => [41],
                'error' => 'absent_users',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    public function testBasicLock()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 40, 'amount' => '1000.00']);

        $this->sendMessage($request->getBlob());

        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0, ['operationUuid' => bin2hex($uuid)]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testBasicLock
     */
    public function testLockMore()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0, ['balance' => '976.00', 'operationUuid' => bin2hex($uuid)]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testBasicLock
     */
    public function testLockFromInsufficientFundsUser()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '10000.00']);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'amount' => '10000.00',
                'balances' => [['user' => 40, 'balance' => '976.00']],
                'error' => 'insufficient_funds'
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testLockMore
     * @depends testLockFromInsufficientFundsUser
     */
    public function testNothingIsChangedAfterFail()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, ['amount' => '0.00', 'balance' => '976.00', 'operationUuid' => bin2hex($uuid)]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }
}
