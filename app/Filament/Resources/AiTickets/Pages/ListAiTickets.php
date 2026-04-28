<?php

namespace App\Filament\Resources\AiTickets\Pages;

use App\Filament\Resources\AiTickets\AiTicketResource;
use App\Models\AiTicket;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ListAiTickets extends ListRecords
{
    protected static string $resource = AiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ নতুন টিকেট'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Page title
    public function getTitle(): string
    {
        return '🎫 AI টিকেটসমূহ';
    }

    public function getSubheading(): ?string
    {
        $total   = AiTicket::count();
        $pending = AiTicket::where('status', 'Pending')->count();
        $solved  = AiTicket::where('status', 'Solve')->count();
        $today   = AiTicket::whereDate('created_at', today())->count();
        return "মোট: {$total} | পেন্ডিং: {$pending} | সমাধান: {$solved} | আজকে: {$today}";
    }
}
