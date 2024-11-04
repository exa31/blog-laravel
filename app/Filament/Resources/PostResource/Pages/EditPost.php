<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\Switch_;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->required(),
                FileUpload::make('image_thumbnail')
                    ->image()
                    ->default(fn($record) => $record->image_thumbnail)
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
                    ->default(state: fn($record) => $record->status)
                    ->inline()
                    ->required(),
            ])
            ->columns(1);
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->before(
                function ($record) {
                    $oldImage = explode('/', $record->image_thumbnail);
                    $url = Storage::disk('public')->exists('images/' . $oldImage[1]);
                    if ($url) {
                        Storage::disk('public')->delete('images/' . $oldImage[1]);
                    }
                }
            )

        ];
    }



    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_at'] = now('Asia/Jakarta')->toDateTimeString();
        return $data;
    }

    protected function getRedirectUrl(): string|null
    {
        return $this->getResource()::getUrl('index');
    }
}
