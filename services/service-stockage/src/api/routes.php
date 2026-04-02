<?php

declare(strict_types=1);

use photopro\stockage\api\actions\stockage\UploadAction;

return static function (\Slim\App $app): void {
    $app->post('/users/{id}/photos', UploadAction::class)
        ->setName('storage.upload');
};
