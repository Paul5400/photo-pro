<?php

use storage\api\middlewares\JwtMiddleware;
use Aws\S3\S3Client;
use storage\infrastructure\storage\StorageService;
use Psr\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

return [
    LoggerInterface::class => function (ContainerInterface $c) {
        $logger = new Logger('storage-api');
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        return $logger;
    },

    JwtMiddleware::class => function (ContainerInterface $c) {
        $secret = $_ENV['JWT_SECRET'] ?? 'default_secret'; // Doit être le même que service-auth
        return new JwtMiddleware($secret);
    },

    S3Client::class => function (ContainerInterface $c) {
        // Client Interne (pour les uploads depuis Docker)
        return new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['S3_REGION'] ?? 'us-east-1',
            'endpoint' => $_ENV['S3_ENDPOINT'] ?? 'http://s3:8333',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $_ENV['S3_ACCESS_KEY'] ?? 'ABCDEF',
                'secret' => $_ENV['S3_SECRET_KEY'] ?? '123456',
            ],
        ]);
    },

    's3.external_client' => function (ContainerInterface $c) {
        // Client Externe (pour générer des liens localhost accessibles par ton navigateur)
        return new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['S3_REGION'] ?? 'us-east-1',
            'endpoint' => 'http://localhost:8333',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $_ENV['S3_ACCESS_KEY'] ?? 'ABCDEF',
                'secret' => $_ENV['S3_SECRET_KEY'] ?? '123456',
            ],
        ]);
    },

    StorageService::class => function (ContainerInterface $c) {
        $s3Client = $c->get(S3Client::class);
        $externalS3Client = $c->get('s3.external_client');
        $bucket = $_ENV['S3_BUCKET'] ?? 'photo-pro';
        $logger = $c->get(LoggerInterface::class);
        return new StorageService($s3Client, $externalS3Client, $bucket, $logger);
    },
];
