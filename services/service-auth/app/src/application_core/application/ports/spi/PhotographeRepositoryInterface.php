<?php
declare(strict_types=1);

namespace photopro\auth\core\application\ports\spi;

use photopro\auth\core\domain\entities\Photographe;

interface PhotographeRepositoryInterface
{
    public function findAll(): array;
    
    public function findById(string $id): ?Photographe;
        
    public function create(Photographe $photographe): Photographe;
    
    
}