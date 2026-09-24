<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\NewsArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_api_creates_post_and_source_matching_user(): void
    {
        Config::set('services.news_api.key', 'test-fake-key');

        Http::fake([
            'newsapi.org/*' => Http::response([
                'status' => 'ok',
                'articles' => [
                    [
                        'title' => 'Breakthrough in AI Quantum Computing Announced',
                        'description' => 'Researchers have achieved an unprecedented quantum milestone.',
                        'content' => 'Full article detailing quantum speedups and architecture.',
                        'urlToImage' => 'https://example.com/quantum.jpg',
                        'source' => [
                            'name' => 'The Verge',
                        ],
                        'url' => 'https://theverge.com/quantum-breakthrough',
                    ],
                ],
            ], 200),
        ]);

        $service = app(NewsArticleService::class);
        $articles = $service->fetchArticles(1);

        $this->assertCount(1, $articles);
        $this->assertEquals('Breakthrough in AI Quantum Computing Announced', $articles[0]['title']);
        $this->assertEquals('The Verge', $articles[0]['author_name']);

        $post = $service->createPostFromArticle($articles[0]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Breakthrough in AI Quantum Computing Announced',
        ]);

        // Assert user matching the source "The Verge" was created
        $this->assertDatabaseHas('users', [
            'name' => 'The Verge',
            'email' => 'the-verge@source.news',
        ]);

        $author = User::where('name', 'The Verge')->first();
        $this->assertEquals($author->id, $post->user_id);
    }

    public function test_dev_to_fallback_creates_post_with_author_user(): void
    {
        Config::set('services.news_api.key', null);

        Http::fake([
            'dev.to/*' => Http::response([
                [
                    'title' => 'Mastering Vue 3 Composition API in 2026',
                    'description' => 'Best practices for organizing composables and reactive stores.',
                    'cover_image' => 'https://example.com/vue.jpg',
                    'tag_list' => ['vue', 'javascript', 'frontend'],
                    'user' => [
                        'name' => 'Sarah Connor',
                        'username' => 'sarahc',
                        'profile_image' => 'https://example.com/sarah.jpg',
                    ],
                    'url' => 'https://dev.to/sarahc/mastering-vue-3',
                ],
            ], 200),
        ]);

        $service = app(NewsArticleService::class);
        $articles = $service->fetchArticles(1);

        $this->assertCount(1, $articles);
        $this->assertEquals('Sarah Connor', $articles[0]['author_name']);

        $post = $service->createPostFromArticle($articles[0]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertDatabaseHas('users', [
            'name' => 'Sarah Connor',
            'email' => 'sarah-connor@source.news',
        ]);

        $this->assertEquals($post->user_id, User::where('name', 'Sarah Connor')->first()->id);
    }

    public function test_duplicate_article_title_is_not_recreated(): void
    {
        $service = app(NewsArticleService::class);

        $article = [
            'title' => 'Unique Tech Article Title',
            'content' => '<p>Some content</p>',
            'image_thumbnail' => 'https://example.com/thumb.jpg',
            'author_name' => 'TechCrunch',
            'author_avatar' => null,
        ];

        $firstPost = $service->createPostFromArticle($article);
        $this->assertNotNull($firstPost);

        // Attempt duplicate creation
        $duplicatePost = $service->createPostFromArticle($article);
        $this->assertNull($duplicatePost);

        $this->assertEquals(1, Post::where('title', 'Unique Tech Article Title')->count());
    }
}
