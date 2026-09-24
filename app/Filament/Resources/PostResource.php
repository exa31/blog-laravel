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
use Filament\Tables\Table;

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
                TextColumn::make('title'),
                TextColumn::make('content')->html()->lineClamp(1),
                TextColumn::make('status'),
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('updated_at')->dateTime(),
            ])
            ->filters([
                //
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
}
