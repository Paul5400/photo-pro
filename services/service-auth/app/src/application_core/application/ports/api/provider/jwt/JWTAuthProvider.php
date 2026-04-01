<?php

declare(strict_types=1);

namespace photopro\auth\core\application\ports\api\provider\jwt;

use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use photopro\auth\core\application\ports\spi\PhotographeRepositoryInterface;
use photopro\auth\core\domain\entities\Photographe;

class JWTAuthProvider implements AuthProviderInterface
{
    private PhotographeRepositoryInterface $photographeRepository;
    private JwtManagerInterface $jwtManager;

    public function __construct(
        PhotographeRepositoryInterface $photographeRepository,
        JwtManagerInterface $jwtManager
    ) {
        $this->photographeRepository = $photographeRepository;
        $this->jwtManager = $jwtManager;
    }

    public function authenticate(string $email, string $password): ?Photographe
    {
        $photographe = $this->photographeRepository->findByEmail($email);

        if (!$photographe) {
            return null;
        }

        if (!password_verify($password, $photographe->password)) {
            return null;
        }

        return $photographe;
    }

    public function generateToken(Photographe $photographe): string
    {
        $payload = $this->jwtManager->createPayload($photographe);
        return $this->jwtManager->encode($payload);
    }

    public function generateRefreshToken(Photographe $photographe): string
    {
        $payload = $this->jwtManager->createRefreshPayload($photographe);
        return $this->jwtManager->encode($payload);
    }

    public function validateToken(string $token): ?array
    {
        return $this->jwtManager->decode($token);
    }

    public function generateVisiteurToken(string $galerieId): string
    {
        $payload = $this->jwtManager->createVisiteurPayload($galerieId);
        return $this->jwtManager->encode($payload);
    }
}
