<?php

return [
    'db' => [
        'class' => \TriAn\IqoTest\core\db\DAO::class,
        'param' => [
            'mysql:host=localhost;dbname=iq_test;charset=utf8',
            'root',
            null,
        ],
    ],
    'logger' => [
        'class' => \TriAn\IqoTest\core\Logger::class,
    ],
    'queue' => [
        'class' => \TriAn\IqoTest\core\Queue::class,
        'param' => [[
            'connection' => ['localhost', 5672, 'guest', 'guest'],
            'inbound_queue' => 'task_queue',
            'outbound_queue' => 'result_queue',
        ]],
    ],
];