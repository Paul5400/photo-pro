<?php

namespace photopro\galeries\api\traits;

trait JwtDecoderTrait
{
    private function extractUserIdFromJwt(string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $pad     = strlen($parts[1]) % 4;
        $padded  = $pad ? $parts[1] . str_repeat('=', 4 - $pad) : $parts[1];
        $payload = json_decode(base64_decode(strtr($padded, '-_', '+/')), true);
        return $payload['sub'] ?? null;
    }
}
