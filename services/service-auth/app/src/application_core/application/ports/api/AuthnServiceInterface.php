<?php

declare(strict_types=1);

namespace photopro\auth\core\application\ports\api;

use photopro\auth\core\application\dto\LoginDTO;
use photopro\auth\core\application\dto\CreatePhotographeDTO;

interface AuthnServiceInterface
{
    public function login(LoginDTO $dto): array;

    public function register(CreatePhotographeDTO $dto): array;
}
