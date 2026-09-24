<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppleIdRevoked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $appleId The Apple User Subject ID ('sub')
     * @param string $eventType The Apple notification event type (e.g., 'consent-revoked', 'account-delete')
     * @param array $payload Full decoded webhook payload from Apple
     * @param User|null $user The resolved User model matching this Apple ID, if found
     */
    public function __construct(
        public string $appleId,
        public string $eventType,
        public array $payload = [],
        public ?User $user = null
    ) {}
}
