<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_hash',
        'jti',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if token is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->getTimestamp() > now()->getTimestamp();
    }

    /**
     * Check if token is within rotation threshold window.
     * E.g., if remaining lifetime is less than or equal to threshold seconds (default 2 days / 48 hours).
     */
    public function shouldRotate(int $thresholdSeconds): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $remainingSeconds = $this->expires_at->getTimestamp() - now()->getTimestamp();

        return $remainingSeconds <= $thresholdSeconds;
    }

    /**
     * Mark this token as revoked.
     */
    public function revoke(): bool
    {
        return $this->update([
            'revoked_at' => now(),
        ]);
    }
}
