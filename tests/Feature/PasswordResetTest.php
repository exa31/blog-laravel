<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResendMailService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
    }

    public function test_requesting_password_reset_creates_token_and_sends_email(): void
    {
        $email = 'reset.create.' . uniqid() . '@example.com';
        $user = User::factory()->create([
            'email' => $email,
        ]);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldReceive('sendPasswordResetEmail')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), Mockery::type('string'))
            ->andReturn(true);
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('password.email'), [
            'email' => $email,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $email,
        ]);
    }

    public function test_reset_password_page_renders_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $plainToken = 'sample-plain-token-123';
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => hash('sha256', $plainToken),
            'created_at' => now(),
        ]);

        $response = $this->get(route('password.reset', ['token' => $plainToken]));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ResetPassword')
            ->where('token', $plainToken)
            ->where('email', 'user@example.com')
        );
    }

    public function test_reset_password_page_redirects_for_invalid_token(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'invalid-nonexistent-token']));
        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors(['invalid']);
    }

    public function test_submitting_valid_password_reset_updates_user_password(): void
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('old_password_123'),
        ]);

        $plainToken = 'test-plain-token-secret-12345';
        DB::table('password_reset_tokens')->insert([
            'email' => 'john.doe@example.com',
            'token' => hash('sha256', $plainToken),
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => $plainToken,
            'password' => 'new_secret_password_888',
            'password_confirm' => 'new_secret_password_888',
        ]);

        $response->assertRedirect(route('home'));

        $user->refresh();
        $this->assertTrue(Hash::check('new_secret_password_888', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'john.doe@example.com',
        ]);
        $this->assertTrue(Auth::check());
    }

    public function test_reset_password_rejects_expired_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'password' => Hash::make('original_pass_123'),
        ]);

        $plainToken = 'expired-token-123';
        DB::table('password_reset_tokens')->insert([
            'email' => 'expired@example.com',
            'token' => hash('sha256', $plainToken),
            'created_at' => Carbon::now()->subMinutes(65), // Expired > 60 mins
        ]);

        $response = $this->post(route('password.update'), [
            'token' => $plainToken,
            'password' => 'new_password_123',
            'password_confirm' => 'new_password_123',
        ]);

        $response->assertSessionHasErrors(['invalid']);
        $user->refresh();
        $this->assertTrue(Hash::check('original_pass_123', $user->password));
    }

    public function test_requesting_password_reset_enforces_cooldown(): void
    {
        $user = User::factory()->create([
            'email' => 'cooldown@example.com',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'cooldown@example.com',
            'token' => hash('sha256', 'recent-token'),
            'created_at' => Carbon::now()->subSeconds(20), // 20 seconds ago (< 60s)
        ]);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldNotReceive('sendPasswordResetEmail');
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('password.email'), [
            'email' => 'cooldown@example.com',
        ]);

        $response->assertSessionHasErrors(['invalid']);
    }

    public function test_requesting_password_reset_enforces_daily_limit_of_three_times(): void
    {
        $user = User::factory()->create([
            'email' => 'daily.limit@example.com',
        ]);

        $dailyKey = 'password-reset-daily:daily.limit@example.com';
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldNotReceive('sendPasswordResetEmail');
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('password.email'), [
            'email' => 'daily.limit@example.com',
        ]);

        $response->assertSessionHasErrors(['invalid']);
    }
}
