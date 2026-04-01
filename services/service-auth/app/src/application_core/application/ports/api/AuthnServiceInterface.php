<?php

declare(strict_types=1);

namespace photopro\auth\core\application\ports\api;

use photopro\auth\core\application\dto\LoginDTO;
use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\dto\ConnexionVisiteurDTO;

interface AuthnServiceInterface
{
    public function login(LoginDTO $dto): array;

    public function register(CreatePhotographeDTO $dto): array;

    public function loginVisiteur(ConnexionVisiteurDTO $dto): array;
}
