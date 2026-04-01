<?php
declare(strict_types=1);

namespace photopro\auth\core\application\ports\api;

use photopro\auth\core\domain\entities\Photographe;
use photopro\auth\core\application\dto\CreatePhotographeDTO;

interface PhotographeServiceInterface
{
    public function getAllPhotographes(): array;
    public function getPhotographeById(string $id): ?Photographe;
    public function createPhotographe(CreatePhotographeDTO $dto): Photographe;
}
