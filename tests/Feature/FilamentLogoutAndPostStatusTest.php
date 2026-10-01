<?php

namespace Tests\Feature;

use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Models\Post;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FilamentLogoutAndPostStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_logout_revokes_tokens_clears_cookies_and_redirects_to_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Filament Admin',
            'email' => 'admin@example.com',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);
        $refreshToken = $jwtService->generateRefreshToken($user);
        $tokenHash = $jwtService->hashToken($refreshToken);

        // Ensure token is active in DB
        $this->assertDatabaseHas('refresh_tokens', [
            'token_hash' => $tokenHash,
            'revoked_at' => null,
        ]);

        // Post to Filament logout endpoint with auth cookies
        $response = $this->actingAs($user)
            ->withUnencryptedCookie('jwt_token', $accessToken)
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->post('/dashboard/logout', [
                'refresh_token' => $refreshToken,
            ]);

        // Assert redirection to login route
        $response->assertRedirect(route('login'));

        // Assert forget cookies are sent in response
        $response->assertCookieExpired('jwt_token');
        $response->assertCookieExpired('refresh_token');

        // Assert DB refresh token is revoked
        $record = RefreshToken::where('token_hash', $tokenHash)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->revoked_at);
    }

    public function test_unauthenticated_access_to_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_post_archived_status_is_excluded_from_public_storefront(): void
    {
        $user = User::factory()->create();

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Published Story',
            'slug' => 'published-story',
            'status' => 'published',
        ]);

        $archivedPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Archived Story',
            'slug' => 'archived-story',
            'status' => 'archived',
        ]);

        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Story',
            'slug' => 'draft-story',
            'status' => 'draft',
        ]);

        // Scopes test
        $this->assertTrue(Post::published()->where('id', $publishedPost->id)->exists());
        $this->assertFalse(Post::published()->where('id', $archivedPost->id)->exists());
        $this->assertTrue(Post::archived()->where('id', $archivedPost->id)->exists());
        $this->assertFalse(Post::archived()->where('id', $publishedPost->id)->exists());
        $this->assertTrue(Post::draft()->where('id', $draftPost->id)->exists());

        // Public index endpoint test
        $response = $this->get('/');
        $response->assertStatus(200);

        // Inertia page props should only contain published post
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('posts', 1)
            ->where('posts.0.id', $publishedPost->id)
        );
    }

    public function test_create_post_mutation_preserves_selected_status(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        // Test mutating form data with 'archived' status
        $page = new class extends CreatePost
        {
            public function mutateDataForTest(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $result = $page->mutateDataForTest([
            'title' => 'My Archived Post',
            'status' => 'archived',
        ]);

        $this->assertEquals('archived', $result['status']);
        $this->assertEquals('my-archived-post', $result['slug']);
        $this->assertEquals($user->id, $result['user_id']);

        // Test default fallback to 'draft' if status is missing
        $resultDraft = $page->mutateDataForTest([
            'title' => 'My Draft Post',
        ]);

        $this->assertEquals('draft', $resultDraft['status']);
    }
}
