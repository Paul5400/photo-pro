<?php

declare(strict_types=1);

use Aws\S3\S3Client;
use photopro\stockage\core\application\exceptions\StorageServiceException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use photopro\stockage\infrastructure\storage\StorageService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    LoggerInterface::class => static function (ContainerInterface $c): LoggerInterface {
        $logger = new Logger('stockage.service');
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        return $logger;
    },

    's3.internal_client' => static function (ContainerInterface $c): S3Client {
        return new S3Client([
            'region' => $c->get('s3.region'),
            'endpoint' => $c->get('s3.internal_endpoint'),
            'use_path_style_endpoint' => true,
            'version' => 'latest',
            'credentials' => [
                'key' => $c->get('s3.key'),
                'secret' => $c->get('s3.secret'),
            ],
        ]);
    },

    's3.external_client' => static function (ContainerInterface $c): S3Client {
        return new S3Client([
            'region' => $c->get('s3.region'),
            'endpoint' => $c->get('s3.external_endpoint'),
            'use_path_style_endpoint' => true,
            'version' => 'latest',
            'credentials' => [
                'key' => $c->get('s3.key'),
                'secret' => $c->get('s3.secret'),
            ],
        ]);
    },

    StorageService::class => static function (ContainerInterface $c): StorageService {
        return new StorageService(
            $c->get('s3.internal_client'),
            $c->get('s3.external_client'),
            (string) $c->get('s3.bucket'),
            $c->get(LoggerInterface::class)
        );
    },
];
