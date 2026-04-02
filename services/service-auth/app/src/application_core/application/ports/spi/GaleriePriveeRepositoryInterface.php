<?php

declare(strict_types=1);

namespace photopro\auth\core\application\ports\spi;

interface GaleriePriveeRepositoryInterface
{
    public function findByUrlAndCode(string $urlAcces, string $codeAcces): ?array;
}
