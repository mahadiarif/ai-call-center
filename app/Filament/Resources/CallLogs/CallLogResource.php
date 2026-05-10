<?php

namespace App\Filament\Resources\CallLogs;

use App\Filament\Resources\CallLogs\Pages\ListCallLogs;
use App\Models\CallLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CallLogResource extends Resource
{
    protected static ?string $model = CallLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Call Logs';

    protected static ?string $pluralModelLabel = 'Call Logs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('latestSrTicket.client_ticket_id')
                    ->label('🎫 Walton SR নং')
                    ->default('—')
                    ->badge()
                    ->color(fn ($state) => $state && $state !== '—' ? 'success' : 'gray')
                    ->copyable()
                    ->tooltip(fn ($record) => $record->latestSrTicket
                        ? "SR ID: {$record->latestSrTicket->id} | {$record->latestSrTicket->customer_name}"
                        : null
                    ),

                TextColumn::make('caller_number')
                    ->label('📱 কলার নম্বর')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->searchable()->copyable(),

                TextColumn::make('ivrService.service_name')
                    ->label('📂 সার্ভিস')
                    ->default('N/A')->badge()->color('info'),

                TextColumn::make('duration_formatted')
                    ->label('⏱️ সময়')
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

                TextColumn::make('recording_path')
                    ->label('🎙️ রেকর্ডিং')
                    ->formatStateUsing(fn ($state) => $state ? '▶ শুনুন' : '—')
                    ->url(fn ($record) => $record->recording_path ? asset('storage/' . $record->recording_path) : null)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('🕐 সময়')
                    ->since()->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('d M Y, h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('📌 স্ট্যাটাস')
                    ->options([
                        'completed' => '✅ Completed',
                        'missed'    => '❌ Missed',
                        'dropped'   => '⚠️ Dropped',
                    ]),

                SelectFilter::make('ivr_service_id')
                    ->label('📂 IVR ক্যাটাগরি')
                    ->relationship('ivrService', 'service_name'),

                \Filament\Tables\Filters\Filter::make('created_at')
                    ->label('📅 তারিখ রেঞ্জ')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('শুরু'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('শেষ'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))
                    ),
            ])
            ->actions([
                \Filament\Actions\DeleteAction::make()->label('')->tooltip('মুছুন'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallLogs::route('/'),
        ];
    }
}
