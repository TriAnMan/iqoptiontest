#!/usr/bin/env php
<?php

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/core/autoload.php');

$config = require(__DIR__ . '/config/config.php');

(new \TriAn\IqoTest\core\App($config))->run();