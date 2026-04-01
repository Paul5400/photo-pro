<?php

declare(strict_types=1);

namespace photopro\auth\core\application\ports\api\provider;

use photopro\auth\core\domain\entities\Photographe;

interface AuthProviderInterface
{
    public function authenticate(string $email, string $password): ?Photographe;

    public function generateToken(Photographe $photographe): string;

    public function generateRefreshToken(Photographe $photographe): string;

    public function validateToken(string $token): ?array;

    public function generateVisiteurToken(string $galerieId): string;
}
