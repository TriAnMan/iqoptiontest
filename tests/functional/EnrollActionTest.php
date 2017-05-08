<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\exception\DBException;
use TriAn\IqoTest\core\exception\MessageParseException;
use TriAn\IqoTest\tests\FunctionalCase;

class EnrollActionTest extends FunctionalCase
{
    public static function setUpBeforeClass()
    {
        static::$requestBody = ['action' => 'enroll', 'user' => 2, 'amount' => '12.53'];
        static::$responseBody = static::$requestBody + ['balance' => '12.53', 'result' => 'ok'];

        parent::setUpBeforeClass();
    }

    public function testBasicEnroll()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testBasicEnroll
     */
    public function testEnrollMore()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0);
        $response = $this->createResponse($uuid, 0, ['balance' => '25.06']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    public function testEnrollTooMuch()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '1000000000.00']);

        $this->expectException(MessageParseException::class);

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testEnrollMore
     * @depends testEnrollTooMuch
     */
    public function testCheckEverythingOk()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, ['amount' => '0.00', 'balance' => '25.06']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }

    /**
     * @depends testBasicEnroll
     */
    public function testEnrollOverflow()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '999999999.99']);

        $this->expectException(DBException::class);
        $this->expectExceptionCode(22003);
        $this->expectExceptionMessage("ANSI: 22003, driver: 1264 - Out of range value for column 'balance' at row 1");

        $this->sendMessage($request->getBlob());
    }

    /**
     * @depends testEnrollMore
     * @depends testEnrollOverflow
     */
    public function testCheckEverythingOk2()
    {
        $uuid = openssl_random_pseudo_bytes(16);
        $request = $this->createRequest($uuid, 0, ['amount' => '0.00']);
        $response = $this->createResponse($uuid, 0, ['amount' => '0.00', 'balance' => '25.06']);

        $this->sendMessage($request->getBlob());

        $this->assertExpectedResponse($response);
    }
}
