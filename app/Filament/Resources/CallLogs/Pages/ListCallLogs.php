<?php

namespace App\Filament\Resources\CallLogs\Pages;

use App\Filament\Resources\CallLogs\CallLogResource;
use App\Models\CallLog;
use Filament\Resources\Pages\ListRecords;

class ListCallLogs extends ListRecords
{
    protected static string $resource = CallLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return "Call Logs";
    }

    public function getSubheading(): ?string
    {
        $total     = CallLog::count();
        $today     = CallLog::whereDate("created_at", today())->count();
        $completed = CallLog::where("status", "completed")->count();
        $dropped   = CallLog::where("status", "dropped")->count();
        return "Total: {$total} | Completed: {$completed} | Dropped: {$dropped} | Today: {$today}";
    }
}
