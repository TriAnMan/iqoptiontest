#!/usr/bin/env php
<?php

/**
 * Do some preparations to have nice errors in CLI
 * @see https://xdebug.org/docs/all_settings#default_enable
 */
ini_set('display_errors', 0);
if (extension_loaded('xdebug')) {
    xdebug_disable(); //Doesn't actually disable xdebug but prevent if from showing stack traces
}

function convertErrors($errno , $errstr, $errfile, $errline) {
    print "ERROR: $errstr in $errfile on line $errline\n\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    trigger_error('Trigger error', E_USER_ERROR);
}

set_error_handler('convertErrors');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/core/autoload.php');

$config = require(__DIR__ . '/config/config.php');

(new \TriAn\IqoTest\core\App($config))->run();