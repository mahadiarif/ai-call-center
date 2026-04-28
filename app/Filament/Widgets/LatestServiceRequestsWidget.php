<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class LatestServiceRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    // Dashboard থেকে সরানো হয়েছে — Service Requests পেজে দেখাবে
    public static function canView(): bool { return false; }

    public function table(Table $table): Table
    {
        return $table
            ->heading('📋 সর্বশেষ সার্ভিস রিকোয়েস্ট')
            ->query(
                ServiceRequest::query()->latest()->limit(8)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#'),

                TextColumn::make('customer_name')
                    ->label('👤 নাম')
                    ->default('N/A'),

                TextColumn::make('mobile_number')
                    ->label('📱 মোবাইল')
                    ->default('N/A'),

                TextColumn::make('ivrService.service_name')
                    ->label('🔧 ক্যাটাগরি')
                    ->badge()
                    ->color('primary')
                    ->default('N/A'),

                TextColumn::make('problem_description')
                    ->label('❗ সমস্যা')
                    ->limit(40)
                    ->default('N/A'),

                TextColumn::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Solve'   => 'success',
                        'Pending' => 'warning',
                        'Follow'  => 'info',
                        default   => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('🕐 সময়')
                    ->since(),
            ])
            ->paginated(false);
    }
}
