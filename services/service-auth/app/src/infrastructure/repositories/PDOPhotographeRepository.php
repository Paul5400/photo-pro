<?php
declare(strict_types=1);

namespace photopro\auth\infra\repositories;

use PDO;
use Ramsey\Uuid\Uuid;
use photopro\auth\core\domain\entities\Photographe;
use photopro\auth\core\application\ports\spi\PhotographeRepositoryInterface;

class PDOPhotographeRepository implements PhotographeRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, nom, pseudo, email, password, telephone, description, created_at
            FROM photographe
            ORDER BY created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Photographe::class);
    }

    public function findById(string $id): ?Photographe
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nom, pseudo, email, password, telephone, description, created_at
            FROM photographe
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Photographe::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?Photographe
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nom, pseudo, email, password, telephone, description, created_at
            FROM photographe
            WHERE email = ?
        ');
        $stmt->execute([$email]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Photographe::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(Photographe $photographe): Photographe
    {
        $id = Uuid::uuid4()->toString();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare('
            INSERT INTO photographe (id, nom, pseudo, email, password, telephone, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id, nom, pseudo, email, password, telephone, description, created_at
        ');

        $stmt->execute([
            $id,
            $photographe->nom,
            $photographe->pseudo,
            $photographe->email,
            $photographe->password,
            $photographe->telephone,
            $photographe->description,
            $now,
        ]);

        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Photographe::class);
        return $stmt->fetch();
    }
}

