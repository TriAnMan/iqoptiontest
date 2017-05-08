<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 08.05.2017
 * Time: 1:11
 */

namespace TriAn\IqoTest\tests\functional;


use TriAn\IqoTest\core\Logger;
use TriAn\IqoTest\core\Message;
use TriAn\IqoTest\tests\FunctionalCase;

class Test extends FunctionalCase
{
    public function testAppIsReporting()
    {
        $this->assertLog(Logger::LEVEL_INFO, 'Run main loop', 'application startup');
    }

    public function testEnroll()
    {
        $requestBody = ['action' => 'enroll', 'user' => 1, 'amount' => '12.00'];
        $responseBody = ['balance' => '12.00', 'result' => 'ok'];
        $request = (new Message("1234567890123456", 0))->setBody((object)$requestBody);
        $response = (new Message("1234567890123456", 0))->setBody((object)($requestBody + $responseBody));

        $this->sendMessage($request->getBlob());

        $this->assertEquals($response->getBlob(), $this->getResponse());
    }
}
