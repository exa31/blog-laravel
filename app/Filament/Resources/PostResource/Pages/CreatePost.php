<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->required(),
                FileUpload::make('image_thumbnail')
                    ->image()
                    ->maxSize('2048')
                    ->disk('public')
                    ->directory('images')
                    ->required(),
                RichEditor::make('content')->required(),
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = \Illuminate\Support\Str::slug($data['title']);
        $data['status'] = 'draft';
        $data['user_id'] = Auth::id();
        $data['created_at'] = now('Asia/Jakarta')->toDateTimeString();
        $data['updated_at'] = now('Asia/Jakarta')->toDateTimeString();
        return $data;
    }

    protected function afterCreateMessage(): string
    {
        return 'Post created!';
    }

    protected function getRedirectUrl(): string
    {

        return $this->getResource()::getUrl('index');
    }

}
