<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use photopro\notifications\core\application\ports\NotificationHandlerInterface;
use photopro\notifications\core\application\ports\MailerInterface;
use photopro\notifications\core\application\usecases\EnvoyerNotificationUseCase;
use photopro\notifications\infra\mailer\SymfonyMailerAdapter;
use photopro\notifications\infra\messaging\RabbitMQConsumer;

return [
    // Connexion AMQP
    AMQPStreamConnection::class => function (ContainerInterface $c): AMQPStreamConnection {
        $cfg = $c->get('amqp');
        return new AMQPStreamConnection(
            $cfg['host'],
            $cfg['port'],
            $cfg['user'],
            $cfg['pass'],
            $cfg['vhost']
        );
    },

    // Port mailer vers adapteur Symfony Mailer vers Mailpit
    MailerInterface::class => function (ContainerInterface $c): MailerInterface {
        $cfg = $c->get('mailer');
        return new SymfonyMailerAdapter($cfg['dsn'], $cfg['from']);
    },

    // Port handler, usecase de traitement des notifications
    NotificationHandlerInterface::class => function (ContainerInterface $c): NotificationHandlerInterface {
        return new EnvoyerNotificationUseCase($c->get(MailerInterface::class));
    },

    // Consumer AMQP
    RabbitMQConsumer::class => function (ContainerInterface $c): RabbitMQConsumer {
        return new RabbitMQConsumer(
            $c->get(AMQPStreamConnection::class),
            $c->get(NotificationHandlerInterface::class),
            $c->get('amqp')
        );
    },
];
