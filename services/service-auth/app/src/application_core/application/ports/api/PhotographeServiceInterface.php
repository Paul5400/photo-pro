<?php

namespace photopro\auth\core\application\ports\api;

use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\domain\entities\photographe\Photographe;

interface PhotographeServiceInterface
{
    public function create(CreatePhotographeDTO $dto): Photographe;

    public function getAll(): array;

    public function getById(string $id): ?Photographe;
}
