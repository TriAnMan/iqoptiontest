<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 19:46
 */

namespace TriAn\IqoTest\core\exception\managed;


use TriAn\IqoTest\core\Message;

class AbsentLock extends ReportableException
{
    /**
     * @param Message $request
     * @return \stdClass response
     */
    public function generateResponse(Message $request)
    {
        $response = $request->getBody();
        $response->error = 'absent_lock';
        return $response;
    }
}