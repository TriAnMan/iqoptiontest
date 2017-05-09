<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\exception\DBException;
use TriAn\IqoTest\core\exception\TransferException;
use TriAn\IqoTest\tests\FunctionalCase;

class TransferActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'transfer', 'fromUser' => 30, 'toUser' => 31, 'amount' => '12.00'];
        static::$responseBody = static::$requestBody + [
            'balances' => [
               ['user' => 30, 'balance' => '988.00'],
               ['user' => 31, 'balance' => '1012.00'],
            ],
            'result' => 'ok',
        ];

        parent::setUpBeforeClass();
    }

    public function testTransfer()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 30, 'amount' => '1000.00']);
        $this->sendMessage($request->getBlob());
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 31, 'amount' => '1000.00']);
        $this->sendMessage($request->getBlob());

        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testTransfer
     */
    public function testReverseTransfer()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 31, 'toUser' => 30]);
        $response = $this->createResponse($uuid, 0, [
            'fromUser' => 31,
            'toUser' => 30,
            'balances' => [
                ['user' => 31, 'balance' => '1000.00'],
                ['user' => 30, 'balance' => '1000.00'],
            ]
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testTransfer
     */
    public function testTransferFromAbsentUser()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 32]);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'fromUser' => 32,
                'absent_users' => [32],
                'error' => 'absent_users',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testTransfer
     */
    public function testTransferToAbsentUser()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['toUser' => 33]);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'toUser' => 33,
                'absent_users' => [33],
                'error' => 'absent_users',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testTransfer
     */
    public function testTransferBetweenAbsentUsers()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 34, 'toUser' => 35]);
        $response = $this->createResponseError(
            $uuid,
            0,
            [
                'fromUser' => 34,
                'toUser' => 35,
                'absent_users' => [34, 35],
                'error' => 'absent_users',
            ]
        );

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testReverseTransfer
     */
    public function testTransferOverflow()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['action' => 'enroll', 'user' => 36, 'amount' => '999999999.99']);
        $this->sendMessage($request->getBlob());

        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 36, 'amount' => '999999999.99']);

        $this->expectException(DBException::class);
        $this->expectExceptionCode(22003);
        $this->expectExceptionMessage("ANSI: 22003, driver: 1264 - Out of range value for column 'balance' at row 1");

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testReverseTransfer
     */
    public function testTransferBetweenEqualAccounts()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['toUser' => 30, 'amount' => '12.00']);

        $this->expectException(TransferException::class);
        $this->expectExceptionMessage('Can\'t transfer money between equal accounts');

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testReverseTransfer
     */
    public function testTransferBetweenEqualAbsentAccounts()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 37, 'toUser' => 37, 'amount' => '12.00']);

        $this->expectException(TransferException::class);
        $this->expectExceptionMessage('Can\'t transfer money between equal accounts');

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testReverseTransfer
     */
    public function testTransferFromUserWithInsufficientFunds()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '10000.00']);
        $response = $this->createResponseError($uuid, 0, [
            'amount' => '10000.00',
            'balances' => [
                ['user' => 30, 'balance' => '1000.00'],
                ['user' => 31, 'balance' => '1000.00'],
            ],
            'error' => 'insufficient_funds',
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testReverseTransfer
     */
    public function testReverseTransferFromUserWithInsufficientFunds()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['fromUser' => 31, 'toUser' => 30, 'amount' => '10000.00']);
        $response = $this->createResponseError($uuid, 0, [
            'fromUser' => 31,
            'toUser' => 30,
            'amount' => '10000.00',
            'balances' => [
                ['user' => 31, 'balance' => '1000.00'],
                ['user' => 30, 'balance' => '1000.00'],
            ],
            'error' => 'insufficient_funds',
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testReverseTransfer
     * @depends testTransferBetweenEqualAccounts
     */
    public function testNothingIsChangedAfterFails()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, [
            'amount' => '0.00',
            'balances' => [
                ['user' => 30, 'balance' => '1000.00'],
                ['user' => 31, 'balance' => '1000.00'],
            ]
        ]);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

}
