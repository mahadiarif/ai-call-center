<?php

namespace App\Filament\Resources\KnowledgeBases\Pages;

use App\Filament\Resources\KnowledgeBases\KnowledgeBaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab; // 🔥 ম্যাজিক ফিক্স: নতুন ভার্সনের সঠিক লোকেশন
use Illuminate\Database\Eloquent\Builder;

class ListKnowledgeBases extends ListRecords
{
    protected static string $resource = KnowledgeBaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // 🔥 ক্যাটাগরি অনুযায়ী আলাদা ট্যাবের ম্যাজিক
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('সবগুলো'),
            'instruction' => Tab::make('এআই নির্দেশনা (Instruction)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'Instruction')),
            'tv' => Tab::make('টিভি (TV)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'TV')),
            'ac' => Tab::make('এসি (AC)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'AC')),
        ];
    }
}