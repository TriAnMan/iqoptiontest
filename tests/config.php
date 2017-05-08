<?php

return [
    'db' => [
        'class' => \TriAn\IqoTest\core\db\DAO::class,
        'param' => [
            'mysql:host=localhost;dbname=iq_test_test;charset=utf8',
            'root',
            null,
        ],
    ],
    'logger' => [
        'class' => \TriAn\IqoTest\tests\LoggerStub::class,
    ],
    'queue' => [
        'class' => \TriAn\IqoTest\tests\QueueStub::class,
        'param' => [[]],
    ],
];