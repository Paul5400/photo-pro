<?php
declare(strict_types=1);

namespace photopro\auth\core\application\ports\api;

use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\dto\LoginDTO;
use photopro\auth\core\domain\entities\Photographe;

interface AuthServiceInterface
{
    public function login(LoginDTO $dto): array;
    public function register(CreatePhotographeDTO $dto): array;
    public function refreshToken(string $refreshToken): array;
    public function generateTokensForUser(Photographe $photographe): array;
}