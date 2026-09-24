<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->exists;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'avatar',
        'email',
        'google_id',
        'apple_id',
        'auth_provider',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
        'has_google_auth',
        'has_apple_auth',
        'can_unbind_apple',
    ];

    /**
     * Check if user has Google authentication linked.
     */
    public function getHasGoogleAuthAttribute(): bool
    {
        return ! empty($this->google_id);
    }

    /**
     * Check if user has Apple authentication linked.
     */
    public function getHasAppleAuthAttribute(): bool
    {
        return ! empty($this->apple_id);
    }

    /**
     * Check if user is allowed to unbind Apple ID.
     * Rule: User can unbind Apple ID if they have another established identity
     * (e.g. Google linked, registered via email/password, or verified non-relay email).
     */
    public function getCanUnbindAppleAttribute(): bool
    {
        if (empty($this->apple_id)) {
            return false;
        }

        // 1. If user has linked Google, they can unbind Apple
        if (! empty($this->google_id)) {
            return true;
        }

        // 2. If user has verified email that is not an Apple private relay, they can unbind Apple
        $isAppleRelay = str_ends_with(strtolower($this->email ?? ''), '@privaterelay.appleid.com');
        if (! empty($this->email_verified_at) && ! $isAppleRelay) {
            return true;
        }

        // 3. If user registered via standard email & password, they can unbind Apple
        if ($this->auth_provider === 'email' && ! empty($this->password)) {
            return true;
        }

        return false;
    }

    /**
     * Get the resolved public URL for the user's avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        $disk = config('filesystems.default', 'public');

        try {
            return Storage::disk($disk)->url($this->avatar);
        } catch (\Throwable $e) {
            return '/storage/'.ltrim($this->avatar, '/');
        }
    }

    /**
     * Get the refresh tokens for the user.
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }
}
