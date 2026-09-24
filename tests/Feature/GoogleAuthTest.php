<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_google_redirect_redirects(): void
    {
        $response = $this->get(route('auth.google'));
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_google_callback_creates_new_user(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google_id_12345');
        $googleUser->shouldReceive('getEmail')->andReturn('alex.google@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Alex Google');
        $googleUser->shouldReceive('getNickname')->andReturn('Alex');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'alex.google@example.com',
            'google_id' => 'google_id_12345',
            'name' => 'Alex Google',
            'auth_provider' => 'google',
        ]);
        $this->assertTrue(Auth::check());
    }

    public function test_google_callback_updates_email_for_apple_registered_user(): void
    {
        // User originally registered with Apple and private relay email
        $user = User::factory()->create([
            'email' => 'privaterelay123@privaterelay.appleid.com',
            'apple_id' => 'apple_sub_7788',
            'google_id' => null,
            'auth_provider' => 'apple',
        ]);

        $this->actingAs($user);

        // User connects Google account
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google_id_99999');
        $googleUser->shouldReceive('getEmail')->andReturn('verified.google.email@gmail.com');
        $googleUser->shouldReceive('getName')->andReturn('Verified User');
        $googleUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect(route('home'));

        // Verify primary email is now updated to the verified Google email!
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'verified.google.email@gmail.com',
            'google_id' => 'google_id_99999',
            'apple_id' => 'apple_sub_7788',
        ]);
    }

    public function test_google_cannot_be_unbound(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google_locked_id_111',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('auth.google.unbind'));
        $response->assertStatus(403);
        $this->assertStringContainsString('cannot be unbound', $response->json('message'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google_locked_id_111',
        ]);
    }
}
