<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppleTokenService
{
    /**
     * Generate or resolve the Apple Client Secret JWT.
     * Apple requires an ES256 signed JWT with:
     * - iss: Team ID
     * - iat: current timestamp
     * - exp: current timestamp + 86400 * 180 (up to 6 months)
     * - aud: https://appleid.apple.com
     * - sub: Client ID / Service ID
     * - Header: alg: ES256, kid: Key ID
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
                'aud' => 'https://appleid.apple.com',
                'sub' => $clientId,
            ];

            return JWT::encode($payload, $keyContent, 'ES256', $keyId);
        } catch (\Throwable $e) {
            Log::error('Failed to generate Apple client_secret JWT: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Decode an unverified or verified Apple JWT (id_token or signed_payload).
     */
    public function decodeToken(string $token): ?array
    {
        try {
            // Split token into parts to decode payload safely
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
        $payload = $this->decodeToken($signedPayload);
        if (! $payload) {
            return null;
        }

        $events = $payload['events'] ?? [];
        $eventType = is_array($events) ? ($events['type'] ?? ($events[0]['type'] ?? 'unknown')) : ($events->type ?? 'unknown');
        $sub = $payload['sub'] ?? ($events['sub'] ?? null);

        // If sub is inside the event object
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
