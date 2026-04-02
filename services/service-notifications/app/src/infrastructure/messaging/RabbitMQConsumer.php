<?php
declare(strict_types=1);

namespace photopro\notifications\infra\messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use photopro\notifications\core\application\ports\NotificationHandlerInterface;

class RabbitMQConsumer
{
    // Routing keys écoutées, correspondent aux événements publiés par service-galeries
    private const ROUTING_KEYS = [
        'gallery.published',
        'gallery.unpublished',
        'gallery.modified',
    ];

    public function __construct(
        private readonly AMQPStreamConnection       $connection,
        private readonly NotificationHandlerInterface $handler,
        private readonly array                      $config
    ) {}

    public function consume(): void
    {
        $channel      = $this->connection->channel();
        $exchangeName = $this->config['exchange'];
        $queueName    = $this->config['queue'];

        // Déclaration de l'exchange (type topic pour le routing par pattern)
        $channel->exchange_declare(
            exchange:    $exchangeName,
            type:        'topic',
            passive:     false,
            durable:     true,
            auto_delete: false
        );

        // Déclaration de la queue
        $channel->queue_declare(
            queue:       $queueName,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
            arguments:   new AMQPTable([
                'x-dead-letter-exchange' => $exchangeName . '.dlx',
            ])
        );

        // Liaison exchange vers queue pour chaque routing key
        foreach (self::ROUTING_KEYS as $routingKey) {
            $channel->queue_bind($queueName, $exchangeName, $routingKey);
        }

        echo sprintf(
            "[Consumer] En écoute sur l'exchange '%s', queue '%s'\n",
            $exchangeName,
            $queueName
        );
        echo sprintf(
            "[Consumer] Routing keys : %s\n",
            implode(', ', self::ROUTING_KEYS)
        );

        // Démarre la consommation, prefetch 1 : un message à la fois
        $channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);
        $channel->basic_consume(
            queue:       $queueName,
            consumer_tag: '',
            no_local:    false,
            no_ack:      false,
            exclusive:   false,
            nowait:      false,
            callback:    $this->buildCallback()
        );

        // Boucle bloquante, le processus reste vivant tant que le canal est ouvert
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }

    private function buildCallback(): \Closure
    {
        return function (AMQPMessage $message): void {
            $routingKey = $message->getRoutingKey();
            echo sprintf("[Consumer] Message reçu — routing_key=%s\n", $routingKey);

            $body = $message->getBody();
            $event = json_decode($body, true);

            if (!is_array($event)) {
                // Message malformé : on le rejette sans requeue (pour éviter les boucles infinies)
                echo "[Consumer] ERREUR : payload JSON invalide, message rejeté.\n";
                $message->nack(requeue: false);
                return;
            }

            // Injection du routing key dans le payload pour que le handler sache le type
            $event['type_event'] = $event['type_event'] ?? $routingKey;

            try {
                $this->handler->handle($event);
                $message->ack();
                echo "[Consumer] Message traité avec succès.\n";
            } catch (\Throwable $e) {
                echo sprintf("[Consumer] ERREUR traitement : %s\n", $e->getMessage());
                $message->nack(requeue: true);
            }
        };
    }
}
