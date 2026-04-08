<?php
declare(strict_types=1);

namespace photopro\galeries\infra\messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publie des événements galerie vers l'exchange RabbitMQ.
 * Routing keys : gallery.published | gallery.unpublished | gallery.modified
 * La connexion est ouverte et fermée à chaque publication (fire-and-forget).
 */
class RabbitMQEventPublisher
{
    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
        private readonly string $exchange,
    ) {}

    public function publish(string $routingKey, array $payload): void
    {
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        $channel    = $connection->channel();

        $channel->exchange_declare($this->exchange, 'topic', false, true, false);

        $msg = new AMQPMessage(
            json_encode($payload),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, 'content_type' => 'application/json']
        );

        $channel->basic_publish($msg, $this->exchange, $routingKey);

        $channel->close();
        $connection->close();
    }
}
