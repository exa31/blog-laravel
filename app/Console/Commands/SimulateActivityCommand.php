<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSyntheticActivityJob;
use Illuminate\Console\Command;

class SimulateActivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:simulate
                            {--posts=2 : Number of posts to generate}
                            {--likes=5 : Number of likes to simulate}
                            {--comments=3 : Number of comments to generate}
                            {--replies=2 : Number of replies to generate}
                            {--async : Dispatch job to queue instead of running synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate user activity by auto-generating posts, users, likes, comments, and replies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $posts = (int) $this->option('posts');
        $likes = (int) $this->option('likes');
        $comments = (int) $this->option('comments');
        $replies = (int) $this->option('replies');
        $async = $this->option('async');

        $this->info("Simulating blog activity: {$posts} posts, {$likes} likes, {$comments} comments, {$replies} replies...");

        $job = new GenerateSyntheticActivityJob($posts, $likes, $comments, $replies);

        if ($async) {
            dispatch($job);
            $this->info('Activity generation job dispatched to the queue successfully.');
        } else {
            dispatch_sync($job);
            $this->info('Activity simulation executed and committed directly to database.');
        }

        return Command::SUCCESS;
    }
}
