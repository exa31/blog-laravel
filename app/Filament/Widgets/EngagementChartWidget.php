<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Post;
use App\Models\SavePost;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EngagementChartWidget extends ChartWidget
{
    protected ?string $heading = 'Community Engagement (Last 7 Days)';

    protected ?string $description = 'Daily breakdown of reader comments, bookmarks, and story publications.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $userId = Auth::id();

        $labels = [];
        $savesData = [];
        $commentsData = [];
        $postsData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStr = $date->toDateString();

            $labels[] = $date->format('D, M j');

            $savesData[] = SavePost::whereHas('post', fn ($q) => $q->where('user_id', $userId))
                ->whereDate('created_at', $dayStr)
                ->count();

            $commentsData[] = Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))
                ->whereDate('created_at', $dayStr)
                ->count();

            $postsData[] = Post::where('user_id', $userId)
                ->whereDate('created_at', $dayStr)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Comments Received',
                    'data' => $commentsData,
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Bookmarks Saved',
                    'data' => $savesData,
                    'borderColor' => '#f43f5e',
                    'backgroundColor' => 'rgba(244, 63, 94, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Stories Published',
                    'data' => $postsData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
