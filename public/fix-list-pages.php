<?php
// Fix ListServiceRequests
$content = '<?php

namespace App\Filament\Resources\ServiceRequests\Pages;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\IvrService;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label("+ নতুন রিকোয়েস্ট"),
        ];
    }

    public function getTitle(): string
    {
        return "Service Requests";
    }

    public function getSubheading(): ?string
    {
        $total   = ServiceRequest::count();
        $pending = ServiceRequest::where("status", "Pending")->count();
        $today   = ServiceRequest::whereDate("created_at", today())->count();
        $drop    = ServiceRequest::where("status", "Drop Call")->count();
        return "Total: {$total} | Pending: {$pending} | Drop Call: {$drop} | Today: {$today}";
    }

    public function getTabs(): array
    {
        $tabs = [
            "all" => Tab::make("All Requests"),
        ];

        $services = IvrService::where("is_active", true)->get();
        foreach ($services as $service) {
            $tabs[$service->service_name] = Tab::make($service->service_name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where("ivr_service_id", $service->id));
        }

        return $tabs;
    }
}
';

file_put_contents(
    dirname(__DIR__) . '/app/Filament/Resources/ServiceRequests/Pages/ListServiceRequests.php',
    $content
);

// Fix ListCallLogs
$callContent = '<?php

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
';

file_put_contents(
    dirname(__DIR__) . '/app/Filament/Resources/CallLogs/Pages/ListCallLogs.php',
    $callContent
);

echo "ListServiceRequests.php updated!\n";
echo "ListCallLogs.php updated!\n";
