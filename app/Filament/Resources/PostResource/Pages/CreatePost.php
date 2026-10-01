<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['title']);
        $data['status'] = $data['status'] ?? 'draft';
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
