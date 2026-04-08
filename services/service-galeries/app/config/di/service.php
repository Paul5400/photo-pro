<?php

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\repositories\PhotoRepositoryInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use photopro\galeries\infra\repositories\PdoRepositorieGalerie;
use photopro\galeries\infra\repositories\PdoPhotoRepository;
use photopro\galeries\infra\http\HttpStorageClient;
use photopro\galeries\infra\messaging\RabbitMQEventPublisher;


return [
    'pdo' => static function ($c): \PDO {
        $dbConfig = $c->get('settings')['database'];
        $host = $dbConfig['host'] ?? 'gallery.db';
        $port = $dbConfig['port'] ?? 5432; 
        $dbname = $dbConfig['database'] ?? 'gallery_db';
        $user = $dbConfig['username'] ?? 'photo_gallery';
        $pass = $dbConfig['password'] ?? 'secret';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);

        return $pdo;
    },
    GalerieRepositoryInterface::class => static function ($c) {
        return new PdoRepositorieGalerie($c->get('pdo'));
    },
    PhotoRepositoryInterface::class => static function ($c) {
        return new PdoPhotoRepository($c->get('pdo'));
    },
    StorageClientInterface::class => static function ($c) {
        $storageUrl = $_ENV['STOCKAGE_URL'] ?? getenv('STOCKAGE_URL') ?? 'http://api.stockage:80';
        return new HttpStorageClient($storageUrl);
    },
    RabbitMQEventPublisher::class => static function ($c) {
        return new RabbitMQEventPublisher(
            host:     $_ENV['RABBITMQ_HOST']     ?? getenv('RABBITMQ_HOST')     ?? 'rabbitmq',
            port:     (int) ($_ENV['RABBITMQ_PORT'] ?? getenv('RABBITMQ_PORT') ?? 5672),
            user:     $_ENV['RABBITMQ_USER']     ?? getenv('RABBITMQ_USER')     ?? 'photopro',
            password: $_ENV['RABBITMQ_PASS']     ?? getenv('RABBITMQ_PASS')     ?? 'photopro',
            vhost:    $_ENV['RABBITMQ_VHOST']    ?? getenv('RABBITMQ_VHOST')    ?? '/',
            exchange: 'photopro.events',
        );
    },
];
