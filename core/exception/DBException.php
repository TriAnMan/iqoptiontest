<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 0:37
 */

namespace TriAn\IqoTest\core\exception;


class DBException extends \RuntimeException
{
    public function __construct($ansiCode, $driverCode, $message)
    {
        parent::__construct("ANSI: $ansiCode, driver: $driverCode - $message", $ansiCode, null);
    }
}