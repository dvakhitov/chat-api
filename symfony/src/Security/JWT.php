<?php

namespace App\Security;

class JWT
{
    private string $publicKey;
    private string $secretKey;

    private string $privateKey;

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function __construct(string $publicKeyPath, string $privateKeyPath, string $secretKey)
    {
        if (file_exists($publicKeyPath) && file_exists($privateKeyPath)) {
            $this->publicKey = file_get_contents($publicKeyPath);
            $this->privateKey = file_get_contents($privateKeyPath);
        } else {
            // Используем секретный ключ для HMAC
            $this->publicKey = $publicKeyPath;
            $this->privateKey = $privateKeyPath;
        }

        $this->secretKey = $secretKey;
    }

    public function getPayload(string $token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        return $payload;
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        $signature = $this->base64UrlDecode($parts[2]);
        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (!$header || !$payload) {
            throw new \InvalidArgumentException('Invalid token content');
        }

        if (!isset($header['alg']) || !in_array($header['alg'], ['RS256', 'HS256'])) {
            throw new \InvalidArgumentException('Invalid algorithm');
        }

        $isValid = $header['alg'] === 'RS256' 
            ? $this->verifyRsaSignature($parts[0] . '.' . $parts[1], $signature)
            : $this->verifyHmacSignature($parts[0] . '.' . $parts[1], $signature);

        if (!$isValid) {
            throw new \InvalidArgumentException('Invalid signature');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \InvalidArgumentException('Token has expired');
        }

        if (!isset($payload['username'])) {
            throw new \InvalidArgumentException('Token missing required field: username');
        }

        return $payload;
    }

    private function verifyRsaSignature(string $data, string $signature): bool
    {
        $verified = openssl_verify(
            $data,
            $signature,
            $this->publicKey,
            OPENSSL_ALGO_SHA256
        );

        return $verified === 1;
    }

    private function verifyHmacSignature(string $data, string $signature): bool
    {
        $expected = hash_hmac('sha256', $data, $this->secretKey, true);

        return hash_equals($expected, $signature);
    }

    private function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $base64 = str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($base64);
    }
} 
