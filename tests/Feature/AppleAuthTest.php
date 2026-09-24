<?php

namespace Tests\Feature;

use App\Events\AppleIdRevoked;
use App\Listeners\HandleAppleIdRevocation;
use App\Models\User;
use App\Services\AppleTokenService;
use App\Services\JwtService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AppleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_apple_redirect_route_redirects(): void
    {
        $response = $this->get(route('auth.apple'));
        $response->assertRedirect();
        $this->assertStringContainsString('appleid.apple.com', $response->headers->get('Location'));
    }

    public function test_apple_callback_redirects_new_user_to_set_password_and_creates_account(): void
    {
        $appleSub = '001234.567890abcdef.1234';
        $appleEmail = 'user@privaterelay.appleid.com';

        $mockTokenService = Mockery::mock(AppleTokenService::class);
        $mockTokenService->shouldReceive('getClientSecret')->andReturn(null);
        $mockTokenService->shouldReceive('verifyIdToken')->andReturn([
            'sub' => $appleSub,
            'email' => $appleEmail,
        ]);
        $mockTokenService->shouldReceive('decodeToken')->andReturn([
            'sub' => $appleSub,
            'email' => $appleEmail,
        ]);
        $this->app->instance(AppleTokenService::class, $mockTokenService);

        // 1. Apple callback redirects to set-password
        $response = $this->post(route('auth.apple.callback'), [
            'id_token' => 'dummy.jwt.token',
            'user' => json_encode([
                'name' => [
                    'firstName' => 'Steve',
                    'lastName' => 'Jobs',
                ],
                'email' => $appleEmail,
            ]),
        ]);

        $response->assertRedirect(route('auth.set-password'));
        $response->assertSessionHas('oauth_pending_user');

        // 2. User enters their desired password
        $submitResponse = $this->post(route('auth.set-password.store'), [
            'password' => 'apple_custom_pass_888',
            'password_confirm' => 'apple_custom_pass_888',
        ]);

        $submitResponse->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'apple_id' => $appleSub,
            'email' => $appleEmail,
            'name' => 'Steve Jobs',
            'auth_provider' => 'apple',
        ]);

        $createdUser = User::where('apple_id', $appleSub)->first();
        $this->assertTrue(Hash::check('apple_custom_pass_888', $createdUser->password));
        $this->assertTrue(Auth::check());
    }

    public function test_apple_callback_links_to_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'apple_id' => null,
        ]);

        $appleSub = '009988.appleid.sub';

        $mockTokenService = Mockery::mock(AppleTokenService::class);
        $mockTokenService->shouldReceive('getClientSecret')->andReturn(null);
        $mockTokenService->shouldReceive('verifyIdToken')->andReturn([
            'sub' => $appleSub,
            'email' => 'existing@example.com',
        ]);
        $mockTokenService->shouldReceive('decodeToken')->andReturn([
            'sub' => $appleSub,
            'email' => 'existing@example.com',
        ]);
        $this->app->instance(AppleTokenService::class, $mockTokenService);

        $response = $this->post(route('auth.apple.callback'), [
            'id_token' => 'dummy.jwt.token',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'apple_id' => $appleSub,
        ]);
    }

    public function test_apple_webhook_dispatches_revocation_event(): void
    {
        Event::fake([AppleIdRevoked::class]);

        $user = User::factory()->create([
            'apple_id' => '001122.revoked.sub',
        ]);

        $mockTokenService = Mockery::mock(AppleTokenService::class);
        $mockTokenService->shouldReceive('parseServerNotification')->andReturn([
            'sub' => '001122.revoked.sub',
            'event_type' => 'consent-revoked',
            'event_time' => time(),
            'payload' => ['events' => ['type' => 'consent-revoked']],
        ]);
        $this->app->instance(AppleTokenService::class, $mockTokenService);

        $response = $this->postJson(route('auth.apple.webhook'), [
            'signed_payload' => 'dummy.signed.jwt',
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(AppleIdRevoked::class, function ($event) {
            return $event->appleId === '001122.revoked.sub' && $event->eventType === 'consent-revoked';
        });
    }

    public function test_apple_revocation_listener_unlinks_apple_id(): void
    {
        $user = User::factory()->create([
            'apple_id' => 'apple_sub_to_revoke_123',
        ]);

        $listener = new HandleAppleIdRevocation(app(JwtService::class));
        $event = new AppleIdRevoked(
            appleId: 'apple_sub_to_revoke_123',
            eventType: 'consent-revoked',
            payload: [],
            user: $user
        );

        $listener->handle($event);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'apple_id' => null,
        ]);
    }

    public function test_cannot_unbind_apple_id_without_alternative_verified_account(): void
    {
        $user = User::factory()->create([
            'apple_id' => 'only_apple_user_id',
            'email' => 'user@privaterelay.appleid.com',
            'auth_provider' => 'apple',
            'google_id' => null, // No google linked
            'email_verified_at' => null,
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('auth.apple.unbind'));
        $response->assertStatus(403);
        $this->assertStringContainsString('verified email', $response->json('message'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'apple_id' => 'only_apple_user_id',
        ]);
    }

    public function test_can_unbind_apple_id_when_google_account_is_linked(): void
    {
        $user = User::factory()->create([
            'apple_id' => 'apple_user_id_123',
            'google_id' => 'google_user_id_456', // Has google linked
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('auth.apple.unbind'));
        $response->assertStatus(200);
        $this->assertStringContainsString('disconnected successfully', $response->json('message'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'apple_id' => null,
            'google_id' => 'google_user_id_456',
        ]);
    }

    public function test_can_unbind_apple_id_when_registered_via_email_password(): void
    {
        $user = User::factory()->create([
            'apple_id' => 'apple_user_id_789',
            'google_id' => null,
            'auth_provider' => 'email',
            'email' => 'author@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('auth.apple.unbind'));
        $response->assertStatus(200);
        $this->assertStringContainsString('disconnected successfully', $response->json('message'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'apple_id' => null,
            'email' => 'author@example.com',
        ]);
    }

    public function test_apple_token_service_returns_null_when_p8_private_key_is_missing(): void
    {
        config([
            'services.apple.client_secret' => null,
            'services.apple.private_key' => null,
            'services.apple.private_key_path' => null,
            'services.apple.team_id' => null,
            'services.apple.key_id' => null,
        ]);

        $service = new AppleTokenService();
        $this->assertNull($service->getClientSecret());
    }

    public function test_apple_token_service_can_decode_unverified_token(): void
    {
        $payload = [
            'sub' => 'apple.user.sub.123',
            'email' => 'test@apple.com',
        ];

        // Create a basic unverified JWT structure: header.payload.signature
        $header = base64_encode(json_encode(['alg' => 'RS256', 'kid' => '12345']));
        $body = base64_encode(json_encode($payload));
        $jwt = "{$header}.{$body}.dummysig";

        $service = new AppleTokenService();
        $decoded = $service->decodeToken($jwt);

        $this->assertIsArray($decoded);
        $this->assertEquals('apple.user.sub.123', $decoded['sub']);
        $this->assertEquals('test@apple.com', $decoded['email']);
    }
}
