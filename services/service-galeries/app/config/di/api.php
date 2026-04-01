<?php

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\infra\repositories\PdoRepositorieGalerie;
use photopro\galeries\core\application\usecases\GalerieService;

return [
    'dependencies' => [
        'interfaces' => [
            GalerieRepositoryInterface::class =>PdoRepositorieGalerie::class,
            GalerieServiceInterface::class => GalerieService::class,
        ],
    ],
];