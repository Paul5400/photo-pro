<?php
declare(strict_types=1);

namespace photopro\galeries\infra\messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use photopro\galeries\core\application\ports\GalerieEventPublisherInterface;

class RabbitMQPublisher implements GalerieEventPublisherInterface
{
    public function __construct(
        private readonly array $config
    ) {}

    public function publish(string $routingKey, array $payload): void
    {
        $connection = new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['user'],
            $this->config['pass'],
            $this->config['vhost']
        );

        $channel = $connection->channel();

        $channel->exchange_declare(
            exchange:    $this->config['exchange'],
            type:        'topic',
            passive:     false,
            durable:     true,
            auto_delete: false
        );

        $message = new AMQPMessage(
            json_encode($payload),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, 'content_type' => 'application/json']
        );

        $channel->basic_publish($message, $this->config['exchange'], $routingKey);

        $channel->close();
        $connection->close();
    }
}
