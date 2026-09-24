<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Comments';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('post_title')
                    ->label('Post')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->post?->title ?? '-'))
                    ->disabled(),
                TextInput::make('user_info')
                    ->label('Author')
                    ->afterStateHydrated(fn ($component, $record) => $component->state(($record?->user?->name ?? 'Unknown').' ('.($record?->user?->email ?? '-').')'))
                    ->disabled(),
                Textarea::make('content')
                    ->label('Comment Body')
                    ->rows(4)
                    ->disabled(),
                Placeholder::make('created_at')
                    ->label('Submitted At')
                    ->content(fn ($record): string => $record?->created_at?->toDayDateTimeString() ?? '-'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post.title')
                    ->label('Article')
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->post?->title)
                    ->color('primary')
                    ->url(fn ($record) => $record->post ? '/post/'.$record->post->slug : null, shouldOpenInNewTab: true),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->description(fn ($record) => $record->user?->email ?? ''),
                TextColumn::make('content')
                    ->label('Comment')
                    ->wrap()
                    ->searchable()
                    ->lineClamp(2),
                TextColumn::make('replies_count')
                    ->counts('replies')
                    ->label('Replies')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Inspect User Comment'),
                Action::make('view_post')
                    ->label('Visit Article')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => $record->post ? '/post/'.$record->post->slug : null, shouldOpenInNewTab: true),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Comment & Replies')
                    ->modalDescription('Are you sure you want to delete this comment? All associated replies will also be permanently removed.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Delete Comments'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('post', function (Builder $query) {
                $query->where('user_id', Auth::id());
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
        ];
    }
}
