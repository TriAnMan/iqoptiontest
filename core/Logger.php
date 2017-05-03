<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 02.05.2017
 * Time: 23:30
 */

namespace TriAn\IqoTest\core;


class Logger
{
    const LEVEL_INFO = 'info';
    const LEVEL_WARN = 'warn';

    private static function log($level, $message)
    {
        $date = \DateTimeImmutable::createFromFormat(
            'U.u T',
            number_format(microtime(true), 6, '.', '' ) . " UTC"
        )->format("Y-m-d H:i:s.u T");
        echo("[${date}][PID " . getmypid() . "][${level}] - ${message}\n");
    }

    public static function info($message)
    {
        static::log(static::LEVEL_INFO, $message);
    }

    public static function warn($message)
    {
        static::log(static::LEVEL_WARN, $message);
    }
}