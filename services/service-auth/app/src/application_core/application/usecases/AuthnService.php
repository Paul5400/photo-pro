<?php

declare(strict_types=1);

namespace photopro\auth\core\application\usecases;

use photopro\auth\core\application\ports\api\AuthnServiceInterface;
use photopro\auth\core\application\dto\LoginDTO;
use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\dto\ConnexionVisiteurDTO;
use photopro\auth\core\application\ports\spi\PhotographeRepositoryInterface;
use photopro\auth\core\application\ports\spi\GaleriePriveeRepositoryInterface;
use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use photopro\auth\core\domain\entities\Photographe;

class AuthnService implements AuthnServiceInterface
{
    private PhotographeRepositoryInterface $photographeRepository;
    private GaleriePriveeRepositoryInterface $galeriePriveeRepository;
    private AuthProviderInterface $authProvider;

    public function __construct(
        PhotographeRepositoryInterface $photographeRepository,
        GaleriePriveeRepositoryInterface $galeriePriveeRepository,
        AuthProviderInterface $authProvider
    ) {
        $this->photographeRepository = $photographeRepository;
        $this->galeriePriveeRepository = $galeriePriveeRepository;
        $this->authProvider = $authProvider;
    }

    public function login(LoginDTO $dto): array
    {
        $photographe = $this->authProvider->authenticate($dto->email, $dto->password);

        if (!$photographe) {
            throw new \Exception('Identifiants invalides', 401);
        }

        $token = $this->authProvider->generateToken($photographe);
        $refreshToken = $this->authProvider->generateRefreshToken($photographe);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'photographe' => [
                'id' => $photographe->id,
                'nom' => $photographe->nom,
                'pseudo' => $photographe->pseudo,
                'email' => $photographe->email,
            ],
        ];
    }

    public function register(CreatePhotographeDTO $dto): array
    {
        $existing = $this->photographeRepository->findByEmail($dto->email);

        if ($existing) {
            throw new \Exception('Email déjà utilisé', 400);
        }

        $photographe = new Photographe(
            null,
            $dto->nom,
            $dto->pseudo,
            $dto->email,
            password_hash($dto->password, PASSWORD_BCRYPT),
            $dto->telephone,
            $dto->description
        );

        $photographe = $this->photographeRepository->create($photographe);

        $token = $this->authProvider->generateToken($photographe);
        $refreshToken = $this->authProvider->generateRefreshToken($photographe);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'photographe' => [
                'id' => $photographe->id,
                'nom' => $photographe->nom,
                'pseudo' => $photographe->pseudo,
                'email' => $photographe->email,
            ],
        ];
    }

    public function loginVisiteur(ConnexionVisiteurDTO $dto): array
    {
        $galeriePrivee = $this->galeriePriveeRepository->findByUrlAndCode(
            $dto->url_acces,
            $dto->code_acces
        );

        if (!$galeriePrivee) {
            throw new \Exception('Code ou URL invalide', 401);
        }

        $token = $this->authProvider->generateVisiteurToken($galeriePrivee['galerie_id']);

        return [
            'token' => $token,
            'galerie_id' => $galeriePrivee['galerie_id'],
        ];
    }
    public function refreshToken(string $refreshToken): array
    {
        $photographe = $this->authProvider->validateRefreshToken($refreshToken);

        if (!$photographe) {
            throw new \Exception('Refresh token invalide', 401);
        }

        $token = $this->authProvider->generateToken($photographe);
        $newRefreshToken = $this->authProvider->generateRefreshToken($photographe);

        return [
            'token' => $token,
            'refresh_token' => $newRefreshToken,
            'photographe' => [
                'id' => $photographe->id,
                'nom' => $photographe->nom,
                'pseudo' => $photographe->pseudo,
                'email' => $photographe->email,
            ],
        ];
    }
}
