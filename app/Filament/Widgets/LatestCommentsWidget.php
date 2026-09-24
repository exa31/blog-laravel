<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestCommentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Reader Comments';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Comment::query()
                    ->whereHas('post', fn ($q) => $q->where('user_id', Auth::id()))
                    ->with(['user', 'post'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Reader')
                    ->weight('bold')
                    ->description(fn ($record) => $record->user?->email),
                TextColumn::make('content')
                    ->label('Comment')
                    ->lineClamp(2)
                    ->wrap(),
                TextColumn::make('post.title')
                    ->label('On Story')
                    ->limit(22)
                    ->color('primary')
                    ->url(fn ($record) => $record->post ? '/post/'.$record->post->slug : null, shouldOpenInNewTab: true),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->actions([
                Action::make('view_comments')
                    ->label('Moderate')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url('/dashboard/comments'),
            ])
            ->paginated(false);
    }
}
