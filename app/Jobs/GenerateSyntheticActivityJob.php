<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Services\NewsArticleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateSyntheticActivityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $postsCount = 1,
        public int $likesCount = 5,
        public int $commentsCount = 3,
        public int $repliesCount = 2
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NewsArticleService $newsService): void
    {
        Log::info("Starting synthetic activity generation: {$this->postsCount} posts, {$this->likesCount} likes, {$this->commentsCount} comments, {$this->repliesCount} replies.");

        // 1. Ensure user pool
        $users = User::all();
        if ($users->count() < 5) {
            $needed = 5 - $users->count();
            User::factory()->count($needed)->create();
            $users = User::all();
        }

        if ($users->isEmpty()) {
            Log::warning('No users available for simulation.');

            return;
        }

        // 2. Generate Posts from Real News / Tech APIs (with factory fallback)
        $createdPosts = collect();
        if ($this->postsCount > 0) {
            try {
                $realArticles = $newsService->fetchArticles($this->postsCount);

                foreach ($realArticles as $articleData) {
                    $post = $newsService->createPostFromArticle($articleData);
                    if ($post) {
                        $createdPosts->push($post);
                    }
                    if ($createdPosts->count() >= $this->postsCount) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Real article fetching failed, using factory fallback: '.$e->getMessage());
            }

            // Fallback to factory if real articles were fewer than requested
            $remaining = $this->postsCount - $createdPosts->count();
            for ($i = 0; $i < $remaining; $i++) {
                $author = $users->random();
                $post = Post::factory()->create([
                    'user_id' => $author->id,
                    'status' => 'published',
                ]);
                $createdPosts->push($post);
            }
        }

        $publishedPosts = Post::where('status', 'published')->get();
        if ($publishedPosts->isEmpty()) {
            return;
        }

        // 3. Generate Likes
        if ($this->likesCount > 0) {
            for ($i = 0; $i < $this->likesCount; $i++) {
                $post = $publishedPosts->random();
                $user = $users->random();

                $alreadyLiked = Like::where('post_id', $post->id)->where('user_id', $user->id)->exists();
                if (! $alreadyLiked) {
                    DB::transaction(function () use ($post, $user) {
                        Like::create([
                            'post_id' => $post->id,
                            'user_id' => $user->id,
                        ]);
                        $post->increment('like');
                    });
                }
            }
        }

        // 4. Generate Comments
        $newComments = collect();
        if ($this->commentsCount > 0) {
            for ($i = 0; $i < $this->commentsCount; $i++) {
                $post = $publishedPosts->random();
                $user = $users->random();

                $comment = Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
                $newComments->push($comment);
            }
        }

        // 5. Generate Replies
        if ($this->repliesCount > 0) {
            $existingComments = Comment::all();
            if ($existingComments->isNotEmpty()) {
                for ($i = 0; $i < $this->repliesCount; $i++) {
                    $comment = $existingComments->random();
                    $user = $users->random();

                    Reply::factory()->create([
                        'comment_id' => $comment->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }

        Log::info('Synthetic activity generation completed successfully.');
    }
}
