<?php
declare(strict_types=1);

namespace photopro\auth\core\application\usecases;

use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\ports\spi\PhotographeRepositoryInterface;
use photopro\auth\core\domain\entities\Photographe;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;

class PhotographeService implements PhotographeServiceInterface
{
    private PhotographeRepositoryInterface $photographeRepository;

    public function __construct(
        PhotographeRepositoryInterface $photographeRepository
    ) {
        $this->photographeRepository = $photographeRepository;
    }

    public function getAllPhotographes(): array
    {
        return $this->photographeRepository->findAll();
    }

    public function getPhotographeById(string $id): ?Photographe
    {
        return $this->photographeRepository->findById($id);
    }

    public function createPhotographe(CreatePhotographeDTO $dto): Photographe
    {
        
        $photographe = new Photographe(
            id: '',
            nom: $dto->nom,
            pseudo: $dto->pseudo,
            email: $dto->email,
            telephone: $dto->telephone,
            description: $dto->description,
            created_at: null
        );
        
        return $this->photographeRepository->create($photographe);
    }
}
