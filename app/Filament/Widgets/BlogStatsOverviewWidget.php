<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\SavePost;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BlogStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = Auth::id();

        $publishedCount = Post::where('user_id', $userId)->where('status', 'published')->count();
        $draftCount = Post::where('user_id', $userId)->where('status', 'draft')->count();
        $archivedCount = Post::where('user_id', $userId)->where('status', 'archived')->count();

        $totalLikes = Like::whereHas('post', fn ($q) => $q->where('user_id', $userId))->count();
        $totalComments = Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))->count();
        $totalSaves = SavePost::whereHas('post', fn ($q) => $q->where('user_id', $userId))->count();

        // Generate 7-day sparkline arrays
        $postsSparkline = [];
        $savesSparkline = [];
        $commentsSparkline = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->toDateString();
            $postsSparkline[] = Post::where('user_id', $userId)
                ->whereDate('created_at', $day)
                ->count();
            $savesSparkline[] = SavePost::whereHas('post', fn ($q) => $q->where('user_id', $userId))
                ->whereDate('created_at', $day)
                ->count();
            $commentsSparkline[] = Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))
                ->whereDate('created_at', $day)
                ->count();
        }

        $breakdown = [];
        if ($draftCount > 0) {
            $breakdown[] = "{$draftCount} draft".($draftCount === 1 ? '' : 's');
        }
        if ($archivedCount > 0) {
            $breakdown[] = "{$archivedCount} archived";
        }
        $description = ! empty($breakdown) ? implode(' · ', $breakdown) : 'All stories published';

        return [
            Stat::make('Published Stories', $publishedCount)
                ->description($description)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart($postsSparkline),

            Stat::make('Community Likes', $totalLikes)
                ->description('Total reactions received')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),

            Stat::make('Reader Comments', $totalComments)
                ->description('Conversations on your posts')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info')
                ->chart($commentsSparkline),

            Stat::make('Bookmarked Stories', $totalSaves)
                ->description('Saved to reading lists')
                ->descriptionIcon('heroicon-m-bookmark')
                ->color('warning')
                ->chart($savesSparkline),
        ];
    }
}
