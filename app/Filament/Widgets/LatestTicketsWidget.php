<?php

namespace App\Filament\Widgets;

use App\Models\AiTicket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class LatestTicketsWidget extends BaseWidget
{
    // Stats widget এর নিচে দেখাবে
    protected static ?int $sort = 2;

    // পুরো width নেবে
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('🎫 সর্বশেষ AI টিকেটসমূহ')
            ->query(
                AiTicket::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('customer_number')
                    ->label('📱 কাস্টমার নম্বর')
                    ->default('N/A'),

                TextColumn::make('ivrService.service_name')
                    ->label('🔧 সার্ভিস')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Resolved' => 'success',
                        'Pending'  => 'warning',
                        default    => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('🕐 সময়')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
