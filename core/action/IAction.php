<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 19:54
 */

namespace TriAn\IqoTest\core\action;


use TriAn\IqoTest\core\Message;

interface IAction
{
    /**
     * @return Message
     */
    public function run();
}