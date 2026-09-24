<?php

namespace App\Filament\Resources\CommentResource\Pages;

use App\Filament\Resources\CommentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return static::getResource()::table($table)
            ->modifyQueryUsing(function (Builder $query) {
                $query->whereHas('post', function (Builder $q) {
                    $q->where('user_id', Auth::id());
                });
            })
            ->defaultSort('created_at', 'desc');
    }
}
