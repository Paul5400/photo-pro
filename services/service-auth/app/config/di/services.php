<?php

use photopro\auth\core\application\ports\PhotographeRepositoryInterface;
use photopro\auth\infra\repositories\PDOPhotographeRepository;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;
use photopro\auth\core\application\usecases\PhotographeService;

return [
    'pdo' => function ($c): \PDO {
        $db = $c->get('settings')['database'];
        $dsn = $db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['database'];
        return new \PDO($dsn, $db['username'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    },

    PhotographeRepositoryInterface::class => function ($c) {
        return new PDOPhotographeRepository($c->get('pdo'));
    },

    PhotographeServiceInterface::class => function ($c) {
        return new PhotographeService($c->get(PhotographeRepositoryInterface::class));
    },
];
