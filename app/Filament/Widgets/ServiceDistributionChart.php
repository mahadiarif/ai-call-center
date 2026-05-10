<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use App\Models\IvrService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ServiceDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Service Distribution';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = ServiceRequest::select('ivr_service_id', DB::raw('count(*) as count'))
            ->groupBy('ivr_service_id')
            ->get();
            
        $labels = [];
        $counts = [];
        
        foreach ($data as $item) {
            $service = IvrService::find($item->ivr_service_id);
            $labels[] = $service ? $service->service_name : 'Unknown';
            $counts[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Service Distribution',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
