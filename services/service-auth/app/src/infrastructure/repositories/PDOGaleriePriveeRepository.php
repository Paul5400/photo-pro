<?php

declare(strict_types=1);

namespace photopro\auth\infra\repositories;

use PDO;
use photopro\auth\core\application\ports\spi\GaleriePriveeRepositoryInterface;

class PDOGaleriePriveeRepository implements GaleriePriveeRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUrlAndCode(string $urlAcces, string $codeAcces): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, galerie_id, nom_client, email_client
            FROM galerie_privee
            WHERE url_acces = ? AND code_acces = ?
        ');
        $stmt->execute([$urlAcces, $codeAcces]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
