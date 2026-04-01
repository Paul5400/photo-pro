<?php

declare(strict_types=1);

namespace photopro\auth\api\provider\jwt;

use photopro\auth\core\domain\entities\Photographe;

interface JwtManagerInterface
{
    public function encode(array $payload): string;

    public function decode(string $token): ?array;

    public function createPayload(Photographe $photographe): array;

    public function createRefreshPayload(Photographe $photographe): array;
}
