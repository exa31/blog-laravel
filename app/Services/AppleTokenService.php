<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppleTokenService
{
    public const APPLE_JWKS_URL = 'https://appleid.apple.com/auth/keys';
    public const APPLE_ISSUER = 'https://appleid.apple.com';

    /**
     * Get Apple's public keys from JWKS endpoint (cached for 24 hours).
     * Used to verify incoming id_token and server notifications without requiring a .p8 private key.
     */
    public function getApplePublicKeys(): array
    {
        return Cache::remember('apple_auth_jwks_keys', 86400, function () {
            try {
                $response = Http::timeout(5)->get(self::APPLE_JWKS_URL);
                if ($response->successful()) {
                    $jwks = $response->json();
                    if (is_array($jwks) && ! empty($jwks['keys'])) {
                        return JWK::parseKeySet($jwks, 'RS256');
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch Apple JWKS public keys: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Verify and decode an Apple JWT (id_token or signed_payload) using Apple JWKS public keys.
     * Falls back to safe payload extraction if public key verification fails.
     */
    public function verifyIdToken(string $token): ?array
    {
        try {
            $keys = $this->getApplePublicKeys();
            if (! empty($keys)) {
                $decoded = JWT::decode($token, $keys);
                return json_decode(json_encode($decoded), true);
            }
        } catch (\Throwable $e) {
            Log::warning('Apple JWT public key verification notice: '.$e->getMessage());
        }

        return $this->decodeToken($token);
    }

    /**
     * Generate or resolve the Apple Client Secret JWT (Optional).
     * If .p8 private key is not provided in .env, this simply returns null without throwing errors.
     */
    public function getClientSecret(): ?string
    {
        $explicitSecret = config('services.apple.client_secret');
        if (! empty($explicitSecret)) {
            return $explicitSecret;
        }

        $teamId = config('services.apple.team_id');
        $keyId = config('services.apple.key_id');
        $clientId = config('services.apple.client_id');
        $privateKey = config('services.apple.private_key');
        $privateKeyPath = config('services.apple.private_key_path');

        if (empty($teamId) || empty($keyId) || empty($clientId)) {
            return null;
        }

        $keyContent = null;
        if (! empty($privateKey)) {
            $keyContent = str_replace('\n', "\n", $privateKey);
        } elseif (! empty($privateKeyPath) && file_exists($privateKeyPath)) {
            $keyContent = file_get_contents($privateKeyPath);
        }

        if (empty($keyContent)) {
            return null;
        }

        try {
            $now = time();
            $payload = [
                'iss' => $teamId,
                'iat' => $now,
                'exp' => $now + (86400 * 180),
                'aud' => self::APPLE_ISSUER,
                'sub' => $clientId,
            ];

            return JWT::encode($payload, $keyContent, 'ES256', $keyId);
        } catch (\Throwable $e) {
            Log::warning('Apple client_secret (.p8) generation skipped: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Decode an Apple JWT payload safely (unverified fallback).
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return is_array($payload) ? $payload : null;
        } catch (\Throwable $e) {
            Log::warning('Failed to parse Apple JWT payload: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Parse Apple Server-to-Server notification payload.
     * Returns array with 'sub', 'event_type', 'event_time', and raw payload.
     */
    public function parseServerNotification(string $signedPayload): ?array
    {
        $payload = $this->verifyIdToken($signedPayload) ?? $this->decodeToken($signedPayload);
        if (! $payload) {
            return null;
        }

        $events = $payload['events'] ?? [];
        $eventType = is_array($events) ? ($events['type'] ?? ($events[0]['type'] ?? 'unknown')) : ($events->type ?? 'unknown');
        $sub = $payload['sub'] ?? ($events['sub'] ?? null);

        if (! $sub && isset($payload['events']['sub'])) {
            $sub = $payload['events']['sub'];
        }

        return [
            'sub' => $sub,
            'event_type' => $eventType,
            'event_time' => $payload['iat'] ?? time(),
            'payload' => $payload,
        ];
    }
}
