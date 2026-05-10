<?php

namespace App\Filament\Resources\SmsLogs;

use App\Models\SmsLog;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class SmsLogResource extends Resource
{
    protected static ?string $model = SmsLog::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
    protected static ?string $navigationLabel = 'SMS Logs';
    protected static \UnitEnum|string|null $navigationGroup = '📊 Analytics & Logs';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                TextColumn::make('mobile')
                    ->label('📱 নম্বর')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('walton_sr')
                    ->label('🎫 Walton SR')
                    ->default('—')
                    ->badge()
                    ->color(fn ($state) => $state && $state !== '—' ? 'success' : 'gray'),

                TextColumn::make('message')
                    ->label('📝 বার্তা')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->message),

                TextColumn::make('type')
                    ->label('ধরন')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'auto'   => 'info',
                        'manual' => 'warning',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'auto'   => '🤖 Auto',
                        'manual' => '👤 Manual',
                        default  => $state,
                    }),

                TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent'    => 'success',
                        'failed'  => 'danger',
                        'pending' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'sent'    => '✅ Sent',
                        'failed'  => '❌ Failed',
                        'pending' => '⏳ Pending',
                        default   => $state,
                    }),

                TextColumn::make('reference_type')
                    ->label('Ticket')
                    ->formatStateUsing(fn ($state, $record) => match ($state) {
                        'sr_ticket' => "SR #{$record->reference_id}",
                        'qm_ticket' => "QM #{$record->reference_id}",
                        default     => '—',
                    })
                    ->default('—'),

                TextColumn::make('sent_by')
                    ->label('পাঠিয়েছেন')
                    ->default('system')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('সময়')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'sent'    => '✅ Sent',
                        'failed'  => '❌ Failed',
                        'pending' => '⏳ Pending',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'auto'   => '🤖 Auto',
                        'manual' => '👤 Manual',
                    ]),
            ])
            ->actions([
                Action::make('view_response')
                    ->label('🔍 Error')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Gateway Response')
                    ->modalContent(function (SmsLog $record) {
                        $resp = $record->gateway_response ?? [];
                        $json = json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $color = $record->status === 'sent' ? '#059669' : '#dc2626';
                        return new \Illuminate\Support\HtmlString(
                            "<div style='font-family:monospace;font-size:0.85rem;'>"
                            . "<div style='margin-bottom:8px;'><strong>Mobile:</strong> {$record->mobile}</div>"
                            . "<div style='margin-bottom:8px;'><strong>Status:</strong> <span style='color:{$color};font-weight:600;'>{$record->status}</span></div>"
                            . "<div style='margin-bottom:8px;'><strong>Message:</strong><br><em style='color:#374151;'>{$record->message}</em></div>"
                            . "<div style='margin-top:12px;'><strong>Gateway Response:</strong></div>"
                            . "<pre style='background:#f3f4f6;padding:12px;border-radius:8px;overflow-x:auto;font-size:0.8rem;margin-top:4px;'>{$json}</pre>"
                            . "</div>"
                        );
                    })
                    ->modalSubmitAction(false),

                Action::make('resend')
                    ->label('🔄 পাঠান')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('SMS পুনরায় পাঠাবেন?')
                    ->action(function (SmsLog $record) {
                        $result = \App\Services\SmsService::sendManual(
                            $record->mobile,
                            $record->message,
                            $record->reference_type ?? '',
                            (int) ($record->reference_id ?? 0),
                            'admin-resend'
                        );
                        if ($result['success']) {
                            Notification::make()->title('✅ SMS পাঠানো হয়েছে!')->success()->send();
                        } else {
                            Notification::make()->title('❌ ' . $result['message'])->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsLogs::route('/'),
        ];
    }
}
