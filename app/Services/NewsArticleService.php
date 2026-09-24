<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsArticleService
{
    /**
     * Fallback high-resolution tech cover images.
     */
    protected array $fallbackImages = [
        'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1200&q=80',
    ];

    /**
     * Fetch real articles from available providers (NewsAPI -> Dev.to -> Empty for factory fallback).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchArticles(int $limit = 5): array
    {
        $articles = [];

        // 1. Try NewsAPI if API key is configured
        $apiKey = config('services.news_api.key');
        if (! empty($apiKey)) {
            $articles = $this->fetchFromNewsApi($apiKey, $limit);
        }

        // 2. Fallback to Dev.to API (Zero-config, free, high quality tech articles)
        if (empty($articles)) {
            $articles = $this->fetchFromDevTo($limit);
        }

        return $articles;
    }

    /**
     * Fetch top articles from NewsAPI.org.
     */
    protected function fetchFromNewsApi(string $apiKey, int $limit): array
    {
        try {
            $category = config('services.news_api.category', 'technology');
            $language = config('services.news_api.language', 'en');

            $response = Http::timeout(8)->get('https://newsapi.org/v2/top-headlines', [
                'apiKey' => $apiKey,
                'category' => $category,
                'language' => $language,
                'pageSize' => max(10, $limit * 2),
            ]);

            if (! $response->successful()) {
                Log::warning('NewsAPI request returned status: '.$response->status());

                return [];
            }

            $rawArticles = $response->json('articles') ?? [];
            $results = [];

            foreach ($rawArticles as $item) {
                if (empty($item['title']) || $item['title'] === '[Removed]') {
                    continue;
                }

                $sourceName = $item['source']['name'] ?? $item['author'] ?? 'Global News';
                $description = $item['description'] ?? 'Breaking development in modern technology and digital innovation.';
                $snippet = $item['content'] ?? $description;
                $cleanedSnippet = preg_replace('/\[\+\d+\s+chars\]$/', '', $snippet);

                $formattedContent = '<h2>Overview</h2>'.
                    "<p>{$description}</p>".
                    '<h3>Details & Analysis</h3>'.
                    "<p>{$cleanedSnippet}</p>".
                    '<blockquote><p>Stay informed with the latest updates curated directly from '.e($sourceName).'.</p></blockquote>'.
                    '<p>For the complete in-depth coverage and related stories, visit the original publication.</p>';

                $results[] = [
                    'title' => trim($item['title']),
                    'content' => $formattedContent,
                    'image_thumbnail' => ! empty($item['urlToImage']) ? $item['urlToImage'] : $this->getRandomFallbackImage(),
                    'author_name' => $sourceName,
                    'author_avatar' => null,
                    'source_url' => $item['url'] ?? null,
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('Error fetching from NewsAPI: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Fetch top tech articles from Dev.to API (No API key required).
     */
    protected function fetchFromDevTo(int $limit): array
    {
        try {
            $response = Http::timeout(8)->get('https://dev.to/api/articles', [
                'per_page' => max(10, $limit * 2),
                'top' => 7,
            ]);

            if (! $response->successful()) {
                Log::warning('Dev.to API request returned status: '.$response->status());

                return [];
            }

            $rawArticles = $response->json() ?? [];
            $results = [];

            foreach ($rawArticles as $item) {
                if (empty($item['title'])) {
                    continue;
                }

                $authorName = $item['user']['name'] ?? $item['user']['username'] ?? 'Tech Author';
                $authorAvatar = $item['user']['profile_image'] ?? null;
                $description = $item['description'] ?? 'Insights into modern web engineering, cloud architecture, and development best practices.';
                $tags = ! empty($item['tag_list']) ? implode(', ', (array) $item['tag_list']) : 'Web Development, Tech';

                $formattedContent = '<h2>Introduction</h2>'.
                    "<p>{$description}</p>".
                    '<h3>Key Architectural Takeaways</h3>'.
                    '<p>Understanding scalable software architecture, modern framework capabilities, and robust backend engineering enables development teams to deliver exceptional user experiences.</p>'.
                    '<blockquote><p>Topic categories: '.e($tags).'</p></blockquote>'.
                    '<h3>Summary</h3>'.
                    '<p>Originally authored by <strong>'.e($authorName).'</strong> on Dev.to. Exploring continuous improvement, clean architecture, and reactive design patterns.</p>';

                $image = $item['cover_image'] ?? $item['social_image'] ?? $this->getRandomFallbackImage();

                $results[] = [
                    'title' => trim($item['title']),
                    'content' => $formattedContent,
                    'image_thumbnail' => $image,
                    'author_name' => $authorName,
                    'author_avatar' => $authorAvatar,
                    'source_url' => $item['url'] ?? null,
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('Error fetching from Dev.to API: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Find or create an authentic user representing the article author/source.
     */
    public function getOrCreateAuthorUser(string $name, ?string $avatarUrl = null): User
    {
        $cleanName = trim($name);
        if (empty($cleanName) || strtolower($cleanName) === '[removed]') {
            $cleanName = 'Tech Dispatch';
        }

        $slug = Str::slug($cleanName);
        if (empty($slug)) {
            $slug = 'author-'.Str::random(6);
        }

        $syntheticEmail = $slug.'@source.news';

        $user = User::where('email', $syntheticEmail)->first();

        if ($user) {
            return $user;
        }

        // Generate 2-character initials as default avatar
        $nameParts = array_values(array_filter(explode(' ', $cleanName)));
        $firstInitial = $nameParts[0][0] ?? 'A';
        $secondInitial = $nameParts[1][0] ?? ($nameParts[0][1] ?? '');
        $initials = strtoupper($firstInitial.$secondInitial);

        $avatar = (! empty($avatarUrl) && filter_var($avatarUrl, FILTER_VALIDATE_URL))
            ? $avatarUrl
            : $initials;

        return User::create([
            'name' => $cleanName,
            'email' => $syntheticEmail,
            'avatar' => $avatar,
            'password' => Hash::make(Str::random(24)),
        ]);
    }

    /**
     * Convert an article payload into a published Post in the database.
     */
    public function createPostFromArticle(array $articleData): ?Post
    {
        $title = $articleData['title'] ?? null;
        if (! $title) {
            return null;
        }

        // Deduplication: check if post with this title already exists
        if (Post::where('title', $title)->exists()) {
            return null;
        }

        $author = $this->getOrCreateAuthorUser(
            $articleData['author_name'] ?? 'News Desk',
            $articleData['author_avatar'] ?? null
        );

        $slug = Str::slug($title).'-'.Str::lower(Str::random(5));

        return Post::create([
            'user_id' => $author->id,
            'title' => $title,
            'slug' => $slug,
            'content' => $articleData['content'] ?? '<p>No content available.</p>',
            'image_thumbnail' => $articleData['image_thumbnail'] ?? $this->getRandomFallbackImage(),
            'status' => 'published',
            'like' => rand(2, 18),
        ]);
    }

    /**
     * Get a random fallback Unsplash tech photo.
     */
    protected function getRandomFallbackImage(): string
    {
        return $this->fallbackImages[array_rand($this->fallbackImages)];
    }
}
