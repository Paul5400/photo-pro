<?php

namespace photopro\auth\core\application\usecases;

use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;
use photopro\auth\core\application\ports\PhotographeRepositoryInterface;
use photopro\auth\core\domain\entities\photographe\Photographe;

class PhotographeService implements PhotographeServiceInterface
{
    private PhotographeRepositoryInterface $repository;

    public function __construct(PhotographeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function create(CreatePhotographeDTO $dto): Photographe
    {
        $photographe = new Photographe(
            null,
            $dto->nom,
            $dto->pseudo,
            $dto->email,
            $dto->telephone,
            $dto->description
        );

        return $this->repository->create($photographe);
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getById(string $id): ?Photographe
    {
        return $this->repository->findById($id);
    }
}
