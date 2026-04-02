<?php

declare(strict_types=1);

return [
    's3.internal_endpoint' => $_ENV['S3_INTERNAL_ENDPOINT'] ?? 'http://S3:8333',
    's3.external_endpoint' => $_ENV['S3_EXTERNAL_ENDPOINT'] ?? 'http://localhost:8333',
    's3.region' => $_ENV['S3_REGION'] ?? 'SeaweedFS',
    's3.key' => $_ENV['S3_ACCESS_KEY'] ?? '',
    's3.secret' => $_ENV['S3_SECRET_KEY'] ?? '',
    's3.bucket' => $_ENV['S3_BUCKET'] ?? 'photos',
];
