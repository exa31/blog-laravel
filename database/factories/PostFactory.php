<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    protected static array $sampleTopics = [
        [
            'title' => 'Mastering Laravel 12: Modern Architecture and High-Performance Patterns',
            'summary' => 'Explore the newest features in Laravel 12, including optimized query engines, streamlined service providers, and zero-downtime queue concurrency.',
            'image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'title' => 'Building Reactive Single-Page Applications with Inertia.js & Vue 3',
            'summary' => 'How to eliminate REST API boilerplate while retaining modern reactive UI capabilities with Inertia 2.0 and Vue 3 Composition API.',
            'image' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'title' => 'The Art of Tactile UI Design: Curing Generic AI Aesthetics',
            'summary' => 'Why micro-interactions, asymmetrical layouts, and curated typography pairings elevate everyday digital experiences.',
            'image' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'title' => 'High-Concurrency Database Optimization with PostgreSQL & Redis',
            'summary' => 'A practical guide to database indexing, connection pooling, and multi-tier caching under heavy production loads.',
            'image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'title' => 'Securing Modern Web Apps with JWT, HttpOnly Cookies, and Zero-Trust',
            'summary' => 'Deep dive into stateless token authentication, mitigation of XSS and CSRF vectors, and secure session management.',
            'image' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'title' => 'Clean Code Principles: Refactoring God Classes and Eliminating Code Smells',
            'summary' => 'Actionable refactoring techniques: early returns, single responsibility principle, and deterministic testing workflows.',
            'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1200&q=80',
        ],
    ];

    public function definition(): array
    {
        $topic = $this->faker->randomElement(self::$sampleTopics);
        $title = $this->faker->boolean(70) ? $topic['title'].' '.$this->faker->words(2, true) : $this->faker->sentence(6);
        $slug = Str::slug($title).'-'.Str::random(5);

        $paragraphs = $this->faker->paragraphs(4);
        $content = "<h2>Introduction</h2><p>{$topic['summary']}</p>".
            "<p>{$paragraphs[0]}</p>".
            '<h3>Key Architectural Insights</h3>'.
            "<p>{$paragraphs[1]}</p>".
            '<blockquote><p>Clean code always looks like it was written by someone who cares.</p></blockquote>'.
            "<p>{$paragraphs[2]}</p>".
            '<h3>Conclusion & Best Practices</h3>'.
            "<p>{$paragraphs[3]}</p>";

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => 'published',
            'image_thumbnail' => $topic['image'],
            'like' => $this->faker->numberBetween(0, 45),
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => now(),
        ];
    }
}
