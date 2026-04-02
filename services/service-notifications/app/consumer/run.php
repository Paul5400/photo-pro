<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use photopro\notifications\infra\messaging\RabbitMQConsumer;

require_once __DIR__ . '/../../vendor/autoload.php';

// Chargement des variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

// Construction du container DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(false);

foreach (['settings', 'services'] as $file) {
    $path = __DIR__ . '/../config/di/' . $file . '.php';
    if (file_exists($path)) {
        $containerBuilder->addDefinitions(require $path);
    }
}

$container = $containerBuilder->build();

// Lancement du consumer, bloque jusqu'à l'arrêt du processus
$container->get(RabbitMQConsumer::class)->consume();
