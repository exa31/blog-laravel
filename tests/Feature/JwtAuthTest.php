<?php

namespace Tests\Feature;

use App\Filament\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_jwt_and_refresh_cookies(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Developer',
            'email' => 'jane@example.com',
            'password' => 'secret1234',
            'password_confirm' => 'secret1234',
        ]);

        $response->assertRedirect('/');
        $response->assertCookie('jwt_token');
        $response->assertCookie('refresh_token');

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('JD', $user->avatar);

        // Verify refresh token is saved in database
        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_user_can_login_with_valid_credentials_and_get_refresh_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => Hash::make('mypassword123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'alex@example.com',
            'password' => 'mypassword123',
        ]);

        $response->assertRedirect('/');
        $response->assertCookie('jwt_token');
        $response->assertCookie('refresh_token');

        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => Hash::make('mypassword123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'alex@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['invalid']);
    }

    public function test_jwt_service_generates_and_validates_access_and_refresh_tokens_with_db_hash(): void
    {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $jwtService = app(JwtService::class);

        $tokens = $jwtService->generateTokens($user);
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);

        // Check DB has hash
        $tokenHash = $jwtService->hashToken($tokens['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'revoked_at' => null,
        ]);

        $resolvedUser = $jwtService->getUserFromToken($tokens['access_token']);
        $this->assertNotNull($resolvedUser);
        $this->assertEquals($user->id, $resolvedUser->id);

        $refreshUser = $jwtService->validateRefreshToken($tokens['refresh_token']);
        $this->assertNotNull($refreshUser);
        $this->assertEquals($user->id, $refreshUser->id);
    }

    public function test_refresh_token_rotates_when_within_threshold(): void
    {
        $user = User::factory()->create(['name' => 'Threshold User', 'email' => 'threshold@example.com']);
        $jwtService = app(JwtService::class);

        $refreshToken = $jwtService->generateRefreshToken($user);
        $tokenHash = $jwtService->hashToken($refreshToken);

        // Simulate token entering rotation threshold (expires in 12 hours <= 2 days threshold)
        RefreshToken::where('token_hash', $tokenHash)->update([
            'expires_at' => Carbon::now()->addHours(12),
        ]);

        $response = $this->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/refresh', [
                'refresh_token' => $refreshToken,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'rotated' => true,
        ]);

        $newRefreshToken = $response->json('refresh_token');
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // Old token should now be revoked in DB
        $this->assertDatabaseHas('refresh_tokens', [
            'token_hash' => $tokenHash,
        ]);
        $oldRecord = RefreshToken::where('token_hash', $tokenHash)->first();
        $this->assertNotNull($oldRecord->revoked_at);

        // New token should exist and be active
        $newHash = $jwtService->hashToken($newRefreshToken);
        $this->assertDatabaseHas('refresh_tokens', [
            'token_hash' => $newHash,
            'revoked_at' => null,
        ]);
    }

    public function test_refresh_token_is_retained_when_outside_threshold(): void
    {
        $user = User::factory()->create(['name' => 'Fresh User', 'email' => 'fresh@example.com']);
        $jwtService = app(JwtService::class);

        $refreshToken = $jwtService->generateRefreshToken($user);

        // Token was just issued (14 days remaining > 2 days threshold)
        $response = $this->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/refresh', [
                'refresh_token' => $refreshToken,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'rotated' => false,
            'refresh_token' => $refreshToken,
        ]);
    }

    public function test_reusing_revoked_token_triggers_reuse_detection(): void
    {
        $user = User::factory()->create(['name' => 'Compromised User', 'email' => 'compromised@example.com']);
        $jwtService = app(JwtService::class);

        // Generate token 1 and token 2
        $token1 = $jwtService->generateRefreshToken($user);
        $token2 = $jwtService->generateRefreshToken($user);

        // Revoke token 1
        $jwtService->revokeRefreshToken($token1);

        // Attacker attempts to reuse revoked token 1
        $validatedUser = $jwtService->validateRefreshToken($token1);
        $this->assertNull($validatedUser);

        // Reuse detection should automatically revoke all remaining active tokens (token 2) for this user
        $hash2 = $jwtService->hashToken($token2);
        $record2 = RefreshToken::where('token_hash', $hash2)->first();
        $this->assertNotNull($record2->revoked_at, 'Token 2 should be revoked by reuse detection');
    }

    public function test_logout_revokes_refresh_token_in_db(): void
    {
        $user = User::factory()->create(['name' => 'Logout User', 'email' => 'logout@example.com']);
        $jwtService = app(JwtService::class);

        $refreshToken = $jwtService->generateRefreshToken($user);
        $tokenHash = $jwtService->hashToken($refreshToken);

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->post('/logout', [
                'refresh_token' => $refreshToken,
            ]);

        $response->assertRedirect('/');

        // DB record should be marked revoked
        $record = RefreshToken::where('token_hash', $tokenHash)->first();
        $this->assertNotNull($record->revoked_at);
    }

    public function test_refresh_token_endpoint_rejects_invalid_token(): void
    {
        $response = $this->withCookie('refresh_token', 'invalid-token-string')
            ->postJson('/refresh');

        $response->assertStatus(401);
    }

    public function test_middleware_transparently_auto_refreshes_and_rotates_when_near_threshold(): void
    {
        $user = User::factory()->create(['name' => 'Transparent User', 'email' => 'trans@example.com']);
        $jwtService = app(JwtService::class);

        $refreshToken = $jwtService->generateRefreshToken($user);
        $tokenHash = $jwtService->hashToken($refreshToken);

        // Near threshold (expires in 6 hours)
        RefreshToken::where('token_hash', $tokenHash)->update([
            'expires_at' => Carbon::now()->addHours(6),
        ]);

        // Request a protected route without access token cookie, but with valid refresh token cookie
        $response = $this->withUnencryptedCookie('refresh_token', $refreshToken)
            ->get('/save-posts');

        $response->assertStatus(200);
        $response->assertCookie('jwt_token');
        $response->assertCookie('refresh_token');

        // Old token revoked
        $oldRecord = RefreshToken::where('token_hash', $tokenHash)->first();
        $this->assertNotNull($oldRecord->revoked_at);
    }

    public function test_authenticated_user_can_access_filament_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        // When authenticated with JWT cookie, /dashboard should be accessible (200 OK)
        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_filament_posts_resource(): void
    {
        $user = User::factory()->create(['name' => 'Author User', 'email' => 'author@example.com']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->get('/dashboard/posts');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_filament_comments_resource(): void
    {
        $user = User::factory()->create(['name' => 'Moderator User', 'email' => 'mod@example.com']);
        $jwtService = app(JwtService::class);
        $tokens = $jwtService->generateTokens($user);

        $response = $this->withUnencryptedCookie('jwt_token', $tokens['access_token'])
            ->withUnencryptedCookie('refresh_token', $tokens['refresh_token'])
            ->get('/dashboard/comments');

        $response->assertStatus(200);
    }

    public function test_filament_comment_resource_only_retrieves_comments_on_own_posts(): void
    {
        $authorA = User::factory()->create(['name' => 'Author A', 'email' => 'authorA@example.com']);
        $authorB = User::factory()->create(['name' => 'Author B', 'email' => 'authorB@example.com']);

        $postA = Post::factory()->create(['user_id' => $authorA->id]);
        $postB = Post::factory()->create(['user_id' => $authorB->id]);

        $commentA = Comment::factory()->create(['post_id' => $postA->id, 'content' => 'Comment on Post A']);
        $commentB = Comment::factory()->create(['post_id' => $postB->id, 'content' => 'Comment on Post B']);

        Auth::login($authorA);
        $commentsForA = CommentResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($commentA->id, $commentsForA);
        $this->assertNotContains($commentB->id, $commentsForA);
    }

    public function test_unauthenticated_user_is_redirected_from_filament_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }
}
