<?php

namespace App\Filament\Resources\AiTickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serviceRequest.customer_name')
                    ->label('👤 নাম')
                    ->searchable()->default('N/A')->sortable(),

                TextColumn::make('serviceRequest.mobile_number')
                    ->label('📱 মোবাইল')
                    ->searchable()->default('N/A')->copyable(),

                TextColumn::make('serviceRequest.ivrService.service_name')
                    ->label('📂 ক্যাটাগরি')
                    ->badge()->color('info')->default('N/A'),

                TextColumn::make('serviceRequest.problem_description')
                    ->label('🗒️ সমস্যা')
                    ->limit(50)->default('—')
                    ->wrap(),

                TextColumn::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Solve'   => 'success',
                        'Follow'  => 'warning',
                        'Pending' => 'info',
                        default   => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('🕐 সময়')
                    ->since()->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('d M Y, h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->options([
                        'Pending' => '⏳ Pending',
                        'Solve'   => '✅ Solved',
                        'Follow'  => '🔄 Follow-up',
                    ]),

                \Filament\Tables\Filters\SelectFilter::make('ivr_service_id')
                    ->label('📂 ক্যাটাগরি')
                    ->relationship('serviceRequest.ivrService', 'service_name')
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->whereHas('serviceRequest', fn ($q) => $q->where('ivr_service_id', $data['value']))
                        : $query
                    ),

                \Filament\Tables\Filters\Filter::make('created_at')
                    ->label('📅 তারিখ রেঞ্জ')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('শুরুর তারিখ'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('শেষ তারিখ'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))
                    ),
            ])
            ->recordActions([
                ViewAction::make()->label('')->tooltip('বিস্তারিত'),
                EditAction::make()->label('')->tooltip('সম্পাদনা'),
                DeleteBulkAction::make()->label('')->tooltip('মুছুন'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('নির্বাচিত মুছুন'),
                ]),
            ]);
    }
}

