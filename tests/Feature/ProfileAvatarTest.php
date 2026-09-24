<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_avatar_file(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $user = User::factory()->create(['name' => 'Alice Test', 'avatar' => null]);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->post('/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'avatar', 'avatar_url'],
            'avatar_url',
        ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringEndsWith('.webp', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_authenticated_user_can_upload_avatar_base64(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $user = User::factory()->create(['name' => 'Bob Test', 'avatar' => null]);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        // 10x10 valid PNG base64
        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAIAAAACUFjqAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAEklEQVQYlWP8z4APMOGVHbHSAEEsARM3dz+eAAAAAElFTkSuQmCC';

        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->post('/profile/avatar', [
                'avatar_base64' => $base64Png,
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringEndsWith('.webp', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_old_avatar_is_cleaned_up_on_new_upload(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $user = User::factory()->create(['name' => 'Charlie Test']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        // First upload
        $file1 = UploadedFile::fake()->image('first.jpg');
        $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->post('/profile/avatar', ['avatar' => $file1]);

        $user->refresh();
        $firstAvatarPath = $user->avatar;
        $this->assertNotNull($firstAvatarPath);
        Storage::disk('public')->assertExists($firstAvatarPath);

        // Second upload
        $file2 = UploadedFile::fake()->image('second.png');
        $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->post('/profile/avatar', ['avatar' => $file2]);

        $user->refresh();
        $secondAvatarPath = $user->avatar;

        $this->assertNotEquals($firstAvatarPath, $secondAvatarPath);
        Storage::disk('public')->assertMissing($firstAvatarPath);
        Storage::disk('public')->assertExists($secondAvatarPath);
    }

    public function test_authenticated_user_can_delete_avatar(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $user = User::factory()->create(['name' => 'Dana Test']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        $file = UploadedFile::fake()->image('temp.jpg');
        $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->post('/profile/avatar', ['avatar' => $file]);

        $user->refresh();
        $avatarPath = $user->avatar;
        $this->assertNotNull($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);

        // Delete avatar
        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->deleteJson('/profile/avatar');

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_unauthenticated_user_cannot_upload_avatar(): void
    {
        $file = UploadedFile::fake()->image('unauthorized.jpg');
        $response = $this->postJson('/profile/avatar', ['avatar' => $file]);
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_display_name(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/profile/name', [
                'name' => 'Updated User Name',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Display name updated successfully!',
            'user' => [
                'id' => $user->id,
                'name' => 'Updated User Name',
            ],
        ]);

        $user->refresh();
        $this->assertEquals('Updated User Name', $user->name);
    }

    public function test_update_name_validates_required_and_length(): void
    {
        $user = User::factory()->create(['name' => 'Valid Name']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        // Empty name
        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/profile/name', [
                'name' => '',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // 1 char name (too short)
        $responseShort = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/profile/name', [
                'name' => 'A',
            ]);
        $responseShort->assertStatus(422);
        $responseShort->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_update_name(): void
    {
        $response = $this->patchJson('/profile/name', ['name' => 'Hacker Name']);
        $response->assertStatus(401);
    }
}
