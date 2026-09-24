<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class JwtService
{
    protected string $secret;

    protected string $algorithm;

    protected int $accessTokenTtl; // 1 hour

    protected int $refreshTokenTtl; // 14 days

    protected int $rotationThreshold; // 2 days threshold for rotation

    public function __construct()
    {
        $appKey = config('app.key') ?? 'base64:default-dev-secret-key-for-blog-jwt';
        if (str_starts_with($appKey, 'base64:')) {
            $this->secret = base64_decode(substr($appKey, 7));
        } else {
            $this->secret = $appKey;
        }

        $this->algorithm = 'HS256';
        $this->accessTokenTtl = 60 * 60; // 1 hour
        $this->refreshTokenTtl = 60 * 60 * 24 * 14; // 14 days
        // Threshold: 2 days (or configurable)
        $this->rotationThreshold = (int) config('jwt.rotation_threshold', 60 * 60 * 24 * 2);
    }

    /**
     * Generate short-lived Access Token.
     */
    public function generateAccessToken(User $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenTtl;

        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'type' => 'access',
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'iat' => $issuedAt,
            'exp' => $expire,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate long-lived Refresh Token and store hash in database.
     */
    public function generateRefreshToken(User $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->refreshTokenTtl;
        $jti = Str::random(32);

        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'type' => 'refresh',
            'sub' => $user->id,
            'jti' => $jti,
            'iat' => $issuedAt,
            'exp' => $expire,
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        // Store hash in database for validation and revocation control
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->hashToken($token),
            'jti' => $jti,
            'expires_at' => Carbon::createFromTimestamp($expire),
        ]);

        return $token;
    }

    /**
     * Generate both Access and Refresh tokens for a User.
     */
    public function generateTokens(User $user): array
    {
        return [
            'access_token' => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user),
            'user' => $user,
        ];
    }

    /**
     * Compute SHA-256 hash of token for safe DB indexing.
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Decode and validate token.
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Find user instance from Access Token.
     */
    public function getUserFromToken(string $token): ?User
    {
        $payload = $this->decodeToken($token);
        if (! $payload || ! isset($payload['sub'])) {
            return null;
        }

        // Must be access token
        if (isset($payload['type']) && $payload['type'] !== 'access') {
            return null;
        }

        return User::find($payload['sub']);
    }

    /**
     * Validate Refresh Token against JWT signature, expiration, and database record.
     * Includes automatic Reuse Detection (revokes all user tokens if revoked token is presented).
     */
    public function validateRefreshToken(string $refreshToken): ?User
    {
        $payload = $this->decodeToken($refreshToken);
        if (! $payload || ! isset($payload['sub']) || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $tokenHash = $this->hashToken($refreshToken);
        $record = RefreshToken::where('token_hash', $tokenHash)->first();

        if (! $record) {
            return null;
        }

        // Token Reuse Detection: If token is presented but already revoked, compromise detected!
        if (! is_null($record->revoked_at)) {
            Log::warning('Security Alert: Revoked refresh token reuse detected for User ID: '.$record->user_id);
            $this->revokeAllUserTokens($record->user_id);

            return null;
        }

        // Expired check in database
        if ($record->expires_at->isPast()) {
            return null;
        }

        return $record->user ?? User::find($payload['sub']);
    }

    /**
     * Rotate Refresh Token when within threshold window (or forced).
     * Returns new access token, rotated (or retained) refresh token, and user instance.
     */
    public function rotateRefreshToken(string $refreshToken, bool $force = false): ?array
    {
        $user = $this->validateRefreshToken($refreshToken);
        if (! $user) {
            return null;
        }

        $tokenHash = $this->hashToken($refreshToken);
        $record = RefreshToken::where('token_hash', $tokenHash)->first();

        $newAccessToken = $this->generateAccessToken($user);
        $shouldRotate = $force || ($record && $record->shouldRotate($this->rotationThreshold));

        if ($shouldRotate) {
            if ($record) {
                $record->revoke();
            }
            $newRefreshToken = $this->generateRefreshToken($user);
        } else {
            $newRefreshToken = $refreshToken;
        }

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'rotated' => $shouldRotate,
            'user' => $user,
        ];
    }

    /**
     * Revoke a single refresh token in database.
     */
    public function revokeRefreshToken(string $refreshToken): void
    {
        $tokenHash = $this->hashToken($refreshToken);
        RefreshToken::where('token_hash', $tokenHash)->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Revoke all active refresh tokens for a user (e.g. On security compromise or full logout).
     */
    public function revokeAllUserTokens(int $userId): void
    {
        RefreshToken::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    /**
     * Build HttpOnly Cookie for Access Token.
     */
    public function getAccessTokenCookie(string $token): Cookie
    {
        $minutes = (int) ($this->accessTokenTtl / 60);

        return cookie('jwt_token', $token, $minutes, '/', null, false, true, false, 'Lax');
    }

    /**
     * Build HttpOnly Cookie for Refresh Token.
     */
    public function getRefreshTokenCookie(string $token): Cookie
    {
        $minutes = (int) ($this->refreshTokenTtl / 60);

        return cookie('refresh_token', $token, $minutes, '/', null, false, true, false, 'Lax');
    }

    /**
     * Build forgotten cookies array for logout.
     */
    public function getForgetCookies(): array
    {
        return [
            cookie()->forget('jwt_token'),
            cookie()->forget('refresh_token'),
        ];
    }
}
