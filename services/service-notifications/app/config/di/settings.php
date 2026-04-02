<?php
declare(strict_types=1);

return [
    'amqp' => [
        'host'     => $_ENV['RABBITMQ_HOST']  ?? 'rabbitmq',
        'port'     => (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
        'user'     => $_ENV['RABBITMQ_USER']  ?? 'photopro',
        'pass'     => $_ENV['RABBITMQ_PASS']  ?? 'photopro',
        'vhost'    => $_ENV['RABBITMQ_VHOST'] ?? '/',
        'exchange' => $_ENV['AMQP_EXCHANGE']  ?? 'photopro.events',
        'queue'    => $_ENV['AMQP_QUEUE']     ?? 'notifications',
    ],

    'mailer' => [
        'dsn'  => $_ENV['MAILER_DSN']  ?? 'smtp://mailpit:1025',
        'from' => $_ENV['MAILER_FROM'] ?? 'noreply@photopro.net',
    ],
];
