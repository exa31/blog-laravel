<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use App\Models\SavePost;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

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
                RichEditor::make('content')
                    ->fileAttachmentsDisk('public')
                    ->disableToolbarButtons(
                        [
                            'attachFiles'
                        ]
                    )
                    ->fileAttachmentsDirectory('attachments')
                    ->required(),
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
            Actions\DeleteAction::make()
                ->before(
                // yg di comment itu cara ngecek pake regex
                    function ($record) {
                        $oldImageThumbnail = explode('/', $record->image_thumbnail);
                        // $oldFileAttachment = $record->content;
                        // $pattern = '/storage\/(.+?)(?:&|$)/';
                        $url = Storage::disk('public')->exists('images/' . $oldImageThumbnail[1]);
                        if ($url) {
                            Storage::disk('public')->delete('images/' . $oldImageThumbnail[1]);
                        }
                        // if (preg_match_all($pattern, $oldFileAttachment, $matches)) {
                        //     $key = 1;
                        //     for ($i = 1; $i <= count($matches[1]) / 3; $i++) {
                        //         $url = Storage::disk('public')->exists($matches[1][$key]);
                        //         if ($url) {
                        //             Storage::disk('public')->delete($matches[1][$key]);
                        //         }
                        //         $key += 3;
                        //     }
                        // }
                    }
                )
        ];
    }


    protected function mutateFormDataBeforeSave(array $data): array
    {
        $lastPost = Post::firstWhere('id', $this->record->id);
        if ($lastPost->image_thumbnail !== $data['image_thumbnail']) {
            $oldImageThumbnail = explode('/', $lastPost->image_thumbnail);
            $url = Storage::disk('public')->exists('images/' . $oldImageThumbnail[1]);
            if ($url) {
                Storage::disk('public')->delete('images/' . $oldImageThumbnail[1]);
            }
        }
        if ($data['status'] === 'draft') {
            SavePost::where('post_id', $this->record->id)
                ->delete();
        }
        return $data;
    }

    protected function getRecordId(): int
    {
        return $this->record->id;
    }

    protected function getRedirectUrl(): string|null
    {
        return $this->getResource()::getUrl('index');
    }
}
