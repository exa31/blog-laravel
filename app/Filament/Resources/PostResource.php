<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Services\ImageOptimizerService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                FileUpload::make('image_thumbnail')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('16:9')
                    ->imageEditorAspectRatios([
                        '16:9',
                        '16:10',
                    ])
                    ->imageEditorMode(2)
                    ->maxSize(10240)
                    ->disk(config('filesystems.default', 'public'))
                    ->directory('images')
                    ->visibility('public')
                    ->saveUploadedFileUsing(function ($file) {
                        return app(ImageOptimizerService::class)->optimizeAndStore(
                            $file,
                            directory: 'images',
                            disk: config('filesystems.default', 'public'),
                            quality: 80,
                            maxWidth: 1920
                        );
                    })
                    ->required(),
                RichEditor::make('content')
                    ->fileAttachmentsDisk(config('filesystems.default', 'public'))
                    ->fileAttachmentsDirectory('attachments')
                    ->fileAttachmentsVisibility('public')
                    ->saveUploadedFileAttachmentUsing(function ($file) {
                        return app(ImageOptimizerService::class)->optimizeAndStore(
                            $file,
                            directory: 'attachments',
                            disk: config('filesystems.default', 'public'),
                            quality: 80,
                            maxWidth: 1920
                        );
                    })
                    ->required(),
                ToggleButtons::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->colors([
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'warning',
                    ])
                    ->icons([
                        'draft' => 'heroicon-o-pencil',
                        'published' => 'heroicon-o-check-circle',
                        'archived' => 'heroicon-o-archive-box',
                    ])
                    ->default('draft')
                    ->inline()
                    ->required(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('content')->html()->lineClamp(1),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'info',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }
}
