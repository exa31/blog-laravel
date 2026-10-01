<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PopularPostsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Performing Stories';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->where('user_id', Auth::id())
                    ->withCount(['likes', 'comments'])
                    ->orderByDesc('likes_count')
                    ->orderByDesc('comments_count')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->weight('bold')
                    ->limit(25)
                    ->color('primary')
                    ->url(fn ($record) => '/post/'.$record->slug, shouldOpenInNewTab: true),
                TextColumn::make('likes_count')
                    ->label('Likes')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'info',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => '/dashboard/posts/'.$record->id.'/edit'),
            ])
            ->paginated(false);
    }
}
