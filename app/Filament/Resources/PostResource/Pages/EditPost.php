<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use App\Models\SavePost;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    function ($record) {
                        if ($record->image_thumbnail && ! str_starts_with($record->image_thumbnail, 'http')) {
                            $disk = config('filesystems.default', 'public');
                            if (Storage::disk($disk)->exists($record->image_thumbnail)) {
                                Storage::disk($disk)->delete($record->image_thumbnail);
                            }
                        }
                    }
                ),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $lastPost = Post::firstWhere('id', $this->record->id);
        if ($lastPost && $lastPost->image_thumbnail !== ($data['image_thumbnail'] ?? null)) {
            if ($lastPost->image_thumbnail && ! str_starts_with($lastPost->image_thumbnail, 'http')) {
                $disk = config('filesystems.default', 'public');
                if (Storage::disk($disk)->exists($lastPost->image_thumbnail)) {
                    Storage::disk($disk)->delete($lastPost->image_thumbnail);
                }
            }
        }
        if (($data['status'] ?? null) === 'draft') {
            SavePost::where('post_id', $this->record->id)
                ->delete();
        }

        return $data;
    }

    protected function getRecordId(): int
    {
        return $this->record->id;
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
