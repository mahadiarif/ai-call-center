<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class LatestCallLogsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    // Dashboard থেকে সরানো হয়েছে — Call Logs পেজে দেখাবে
    public static function canView(): bool { return false; }

    public function table(Table $table): Table
    {
        return $table
            ->heading('📞 সর্বশেষ কল লগ')
            ->query(
                CallLog::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#'),

                TextColumn::make('caller_number')
                    ->label('📱 কলার নম্বর')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->default('—'),

                TextColumn::make('ivrService.service_name')
                    ->label('🔧 IVR সার্ভিস')
                    ->badge()
                    ->color('info')
                    ->default('N/A'),

                TextColumn::make('duration_formatted')
                    ->label('⏱️ কথার সময়')
                    ->default('00:00'),

                TextColumn::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'missed'    => 'danger',
                        'dropped'   => 'warning',
                        default     => 'gray',
                    }),

                // recording_path কলাম Dashboard থেকে সরানো হয়েছে
                // Call Logs পেজে toggle করে দেখা যাবে

                TextColumn::make('created_at')
                    ->label('🕐 সময়')
                    ->since(),
            ])
            ->paginated(false);
    }
}
