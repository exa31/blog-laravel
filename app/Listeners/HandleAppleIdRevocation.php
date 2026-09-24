<?php

namespace App\Listeners;

use App\Events\AppleIdRevoked;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleAppleIdRevocation
{
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Handle the Apple ID revocation event.
     */
    public function handle(AppleIdRevoked $event): void
    {
        Log::info("Processing Apple ID revocation event [{$event->eventType}] for Apple sub: {$event->appleId}");

        $user = $event->user ?? User::where('apple_id', $event->appleId)->first();

        if (! $user) {
            Log::warning("No user found matching Apple ID: {$event->appleId} during revocation handling.");
            return;
        }

        // Unbind the Apple ID from user
        $user->update([
            'apple_id' => null,
        ]);

        // Revoke active sessions / refresh tokens
        $this->jwtService->revokeAllUserTokens($user->id);

        Log::info("Successfully unbound Apple ID for User #{$user->id} ({$user->email}) following Apple {$event->eventType} notification.");
    }
}
