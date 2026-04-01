<?php

declare(strict_types=1);

namespace photopro\auth\api\provider\jwt;

use photopro\auth\core\domain\entities\Photographe;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTManager implements JwtManagerInterface
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expirationDays = 7;
    private int $refreshExpirationDays = 30;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function encode(array $payload): string
    {
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return json_decode(json_encode($decoded), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createPayload(Photographe $photographe): array
    {
        $now = time();

        return [
            'iss' => 'photopro-auth',
            'iat' => $now,
            'exp' => $now + ($this->expirationDays * 24 * 60 * 60),
            'sub' => $photographe->id,
            'user' => [
                'id' => $photographe->id,
                'pseudo' => $photographe->pseudo,
                'email' => $photographe->email,
            ],
        ];
    }

    public function createRefreshPayload(Photographe $photographe): array
    {
        $now = time();

        return [
            'iss' => 'photopro-auth',
            'iat' => $now,
            'exp' => $now + ($this->refreshExpirationDays * 24 * 60 * 60),
            'sub' => $photographe->id,
            'type' => 'refresh',
        ];
    }
}
