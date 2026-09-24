<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    protected static array $sampleComments = [
        'Great breakdown! The architectural explanation really clarifies how to structure modular components.',
        'This solved an issue I was facing with connection concurrency last week. Thanks for sharing!',
        'Very insightful writeup. How does this compare with the alternative approach in terms of memory overhead?',
        'Clear, concise, and straight to the point. Bookmarked for my team to review.',
        'Fantastic article! Looking forward to seeing more deep dives like this on the blog.',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'content' => $this->faker->boolean(70) ? $this->faker->randomElement(self::$sampleComments) : $this->faker->paragraph(2),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}
