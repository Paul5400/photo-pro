<?php

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\infra\repositories\PdoRepositorieGalerie;


return [
    'pdo' => static function ($c): \PDO {
        $dbConfig = $c->get('settings')['database'];
        $host = $dbConfig['host'] ?? 'alt_db';
        $port = $dbConfig['port'] ?? 5432; 
        $dbname = $dbConfig['database'] ?? 'alt_social';
        $user = $dbConfig['username'] ?? 'alt';
        $pass = $dbConfig['password'] ?? 'alt';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);

        return $pdo;
    },
    //reposository
    GalerieRepositoryInterface::class => static function ($c) {
        return new PdoRepositorieGalerie($c->get('pdo'));
    },];