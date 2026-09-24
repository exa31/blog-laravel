<?php

namespace Tests\Feature;

use App\Jobs\GenerateSyntheticActivityJob;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitySimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_job_generates_users_posts_likes_and_comments(): void
    {
        $job = new GenerateSyntheticActivityJob(
            postsCount: 2,
            likesCount: 3,
            commentsCount: 2,
            repliesCount: 2
        );

        dispatch_sync($job);

        $this->assertGreaterThanOrEqual(5, User::count());
        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Comment::count());
    }

    public function test_artisan_simulation_command_runs_successfully(): void
    {
        $this->artisan('blog:simulate', [
            '--posts' => 1,
            '--likes' => 2,
            '--comments' => 1,
            '--replies' => 1,
        ])->assertSuccessful();

        $this->assertEquals(1, Post::count());
    }
}
