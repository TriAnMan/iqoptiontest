<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use PDOException;
use TriAn\IqoTest\tests\FunctionalCase;

class EnrollActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'enroll', 'user' => 10, 'amount' => '12.53'];
        static::$responseBody = static::$requestBody + ['balance' => '12.53', 'result' => 'ok'];

        parent::setUpBeforeClass();
    }

    public function testEnrollAbsentUserAccount()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testEnrollAbsentUserAccount
     */
    public function testEnrollPresentUserAccount()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0, ['balance' => '25.06']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testEnrollAbsentUserAccount
     */
    public function testEnrollOverflow()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '999999999.99']);

        $this->expectException(PDOException::class);
        $this->expectExceptionCode(22003);
        $this->expectExceptionMessage("SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'balance' at row 1");

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testEnrollPresentUserAccount
     * @depends testEnrollOverflow
     */
    public function testNothingIsChangedAfterFail()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, ['amount' => '0.00', 'balance' => '25.06']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }
}
