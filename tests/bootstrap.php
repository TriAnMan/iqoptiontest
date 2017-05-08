<?php

print "Creating DB schema...";
exec("/usr/bin/env mysql iq_test_test < " . __DIR__ . "/../db_schema.sql");
print "\tDone\n";

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../core/autoload.php');

defined('IQ_TEST_ROOT') or define('IQ_TEST_ROOT', realpath(__DIR__ . '/../'));