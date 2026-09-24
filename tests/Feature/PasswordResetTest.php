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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
    }

    public function test_requesting_password_reset_creates_token_and_sends_email(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.test@example.com',
        ]);

        $mockMailService = Mockery::mock(ResendMailService::class);
        $mockMailService->shouldReceive('sendPasswordResetEmail')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), Mockery::type('string'))
            ->andReturn(true);
        $this->app->instance(ResendMailService::class, $mockMailService);

        $response = $this->post(route('password.email'), [
            'email' => 'reset.test@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset.test@example.com',
        ]);
    }

    public function test_reset_password_page_renders_with_token(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'user@example.com']));
        $response->assertStatus(200);
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
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => $plainToken,
            'email' => 'john.doe@example.com',
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
            'token' => Hash::make($plainToken),
            'created_at' => Carbon::now()->subMinutes(65), // Expired > 60 mins
        ]);

        $response = $this->post(route('password.update'), [
            'token' => $plainToken,
            'email' => 'expired@example.com',
            'password' => 'new_password_123',
            'password_confirm' => 'new_password_123',
        ]);

        $response->assertSessionHasErrors(['invalid']);
        $user->refresh();
        $this->assertTrue(Hash::check('original_pass_123', $user->password));
    }
}
