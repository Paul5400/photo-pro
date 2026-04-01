<?php

use photopro\auth\core\application\ports\api\PhotographeServiceInterface;
use photopro\auth\core\application\ports\api\AuthnServiceInterface;
use photopro\auth\core\application\ports\spi\PhotographeRepositoryInterface;
use photopro\auth\core\application\ports\spi\GaleriePriveeRepositoryInterface;
use photopro\auth\infra\repositories\PDOPhotographeRepository;
use photopro\auth\infra\repositories\PDOGaleriePriveeRepository;
use photopro\auth\core\application\usecases\PhotographeService;
use photopro\auth\core\application\usecases\AuthnService;
use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use photopro\auth\core\application\ports\api\provider\jwt\JwtManagerInterface;
use photopro\auth\core\application\ports\api\provider\jwt\JWTManager;
use photopro\auth\core\application\ports\api\provider\jwt\JWTAuthProvider;

return [
    'pdo' => function ($c): \PDO {
        $db = $c->get('settings')['database'];
        $dsn = $db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['database'];
        return new \PDO($dsn, $db['username'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    },

    'pdo.galerie' => function ($c): \PDO {
        $db = $c->get('settings')['gallery_database'];
        $dsn = $db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['database'];
        return new \PDO($dsn, $db['username'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    },

    PhotographeRepositoryInterface::class => function ($c) {
        return new PDOPhotographeRepository($c->get('pdo'));
    },

    GaleriePriveeRepositoryInterface::class => function ($c) {
        return new PDOGaleriePriveeRepository($c->get('pdo.galerie'));
    },

    PhotographeServiceInterface::class => function ($c) {
        return new PhotographeService($c->get(PhotographeRepositoryInterface::class));
    },

    JwtManagerInterface::class => function ($c) {
        return new JWTManager($c->get('settings')['jwt']['secret']);
    },

    AuthProviderInterface::class => function ($c) {
        return new JWTAuthProvider(
            $c->get(PhotographeRepositoryInterface::class),
            $c->get(JwtManagerInterface::class)
        );
    },

    AuthnServiceInterface::class => function ($c) {
        return new AuthnService(
            $c->get(PhotographeRepositoryInterface::class),
            $c->get(GaleriePriveeRepositoryInterface::class),
            $c->get(AuthProviderInterface::class)
        );
    },
];
