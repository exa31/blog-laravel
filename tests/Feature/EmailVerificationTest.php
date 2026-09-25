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

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_registration_sends_verification_code_and_redirects_to_verify_page(): void
    {
        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldReceive('sendVerificationCodeEmail')
            ->once()
            ->andReturn(true);
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('register.post'), [
            'name' => 'Sara Connor',
            'email' => 'sara@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('verify_email_address', 'sara@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'sara@example.com',
            'name' => 'Sara Connor',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        $this->assertDatabaseHas('email_verification_codes', [
            'email' => 'sara@example.com',
        ]);
    }

    public function test_verify_page_renders_with_email_in_session(): void
    {
        $response = $this->withSession(['verify_email_address' => 'sara@example.com'])
            ->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('VerifyEmail')
            ->where('email', 'sara@example.com')
        );
    }

    public function test_submitting_valid_code_verifies_email_and_logs_in(): void
    {
        $user = User::factory()->create([
            'email' => 'sara@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        DB::table('email_verification_codes')->insert([
            'email' => 'sara@example.com',
            'code' => Hash::make('654321'),
            'created_at' => now(),
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => 'sara@example.com',
            'code' => '654321',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertCookie('jwt_token');
        $response->assertCookie('refresh_token');

        $this->assertTrue(Auth::check());
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Verification code should be deleted
        $this->assertDatabaseMissing('email_verification_codes', [
            'email' => 'sara@example.com',
        ]);
    }

    public function test_submitting_invalid_code_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'sara@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        DB::table('email_verification_codes')->insert([
            'email' => 'sara@example.com',
            'code' => Hash::make('654321'),
            'created_at' => now(),
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => 'sara@example.com',
            'code' => '111222',
        ]);

        $response->assertSessionHasErrors('invalid');
        $this->assertFalse(Auth::check());
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_submitting_expired_code_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'sara@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        DB::table('email_verification_codes')->insert([
            'email' => 'sara@example.com',
            'code' => Hash::make('654321'),
            'created_at' => Carbon::now()->subMinutes(20), // Expired > 15 mins
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => 'sara@example.com',
            'code' => '654321',
        ]);

        $response->assertSessionHasErrors('invalid');
        $this->assertFalse(Auth::check());
    }

    public function test_resending_code_generates_new_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'resend.valid@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldReceive('sendVerificationCodeEmail')
            ->once()
            ->andReturn(true);
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->postJson(route('verification.resend'), [
            'email' => 'resend.valid@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('email_verification_codes', [
            'email' => 'resend.valid@example.com',
        ]);
    }

    public function test_resending_code_is_throttled_within_cooldown(): void
    {
        $user = User::factory()->create([
            'email' => 'resend.throttled@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        DB::table('email_verification_codes')->insert([
            'email' => 'resend.throttled@example.com',
            'code' => Hash::make('123456'),
            'created_at' => now(), // Sent just now
        ]);

        $response = $this->post(route('verification.resend'), [
            'email' => 'resend.throttled@example.com',
        ]);

        $response->assertSessionHasErrors('invalid');
    }

    public function test_unverified_user_login_redirects_to_verification_page(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified.login@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldReceive('sendVerificationCodeEmail')
            ->once()
            ->andReturn(true);
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('login.post'), [
            'email' => 'unverified.login@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertFalse(Auth::check());
    }

    public function test_resending_code_enforces_daily_limit_of_three_times(): void
    {
        $user = User::factory()->create([
            'email' => 'daily.otp@example.com',
            'email_verified_at' => null,
            'auth_provider' => 'email',
        ]);

        $dailyKey = 'verify-resend-daily:daily.otp@example.com';
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldNotReceive('sendVerificationCodeEmail');
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('verification.resend'), [
            'email' => 'daily.otp@example.com',
        ]);

        $response->assertSessionHasErrors('invalid');
    }
}
