<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    protected static array $sampleReplies = [
        'Totally agree with you on this point!',
        'In terms of memory overhead, benchmarks show approximately 15-20% lower allocation.',
        'Thanks! Glad it was helpful for your team.',
        'Good observation! We will definitely cover the benchmarks in our upcoming article.',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'comment_id' => Comment::factory(),
            'body' => fake()->boolean(70) ? fake()->randomElement(self::$sampleReplies) : fake()->sentence(8),
            'created_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'updated_at' => now(),
        ];
    }
}
