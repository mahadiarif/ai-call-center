<?php

namespace App\Filament\Resources\SrTickets;

use App\Models\SrTicket;
use App\Models\IvrService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class SrTicketResource extends Resource
{
    protected static ?string $model = SrTicket::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
    protected static ?string $navigationLabel = 'SR Tickets';
    protected static ?string $modelLabel = 'SR Ticket';
    protected static string | \UnitEnum | null $navigationGroup = '📞 Call Operations';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        // Walton cached data থেকে dropdown options
        $waltonProducts = [
            'REFRIGERATOR'        => 'REFRIGERATOR (ফ্রিজ)',
            'FREEZER'             => 'FREEZER (ফ্রিজার)',
            'AIRCONDITIONER'      => 'AIRCONDITIONER (এসি)',
            'LED TELEVISION'      => 'LED TELEVISION (টিভি)',
            'LED SMART TELEVISION'=> 'LED SMART TELEVISION (স্মার্ট টিভি)',
            '3D TELEVISION'       => '3D TELEVISION',
            'LCD TELEVISION'      => 'LCD TELEVISION',
            'COLOR TELEVISION'    => 'COLOR TELEVISION',
            'CHILLER'             => 'CHILLER (চিলার)',
            'BEVERAGE COOLER'     => 'BEVERAGE COOLER',
            'FREEZER'             => 'FREEZER',
            'TV TUNER'            => 'TV TUNER',
            'AIRCONDITIONER-INDOOR'  => 'AIRCONDITIONER-INDOOR',
            'AIRCONDITIONER-OUTDOOR' => 'AIRCONDITIONER-OUTDOOR',
            'VRF AIR CONDITIONER' => 'VRF AIR CONDITIONER',
            'Fan Regulator'       => 'Fan Regulator',
            'TELEVISION'          => 'TELEVISION',
        ];

        // Service Center — Walton cached data থেকে
        $serviceCenters = \App\Models\ClientDataCache::where('data_type', 'service_center')
            ->limit(200)
            ->get()
            ->mapWithKeys(function ($item) {
                $d    = $item->raw_data ?? [];
                $code = $d['SERVICE_CENTER_ID'] ?? $d['code'] ?? $d['id'] ?? $item->external_id ?? '';
                $name = $d['SERVICE_CENTER_NAME'] ?? $d['name'] ?? $d['SERVICE_CENTER'] ?? $code;
                return [$code => "{$name} ({$code})"];
            })
            ->filter()
            ->toArray();

        return $schema->components([
            TextInput::make('mobile_number')->label('Mobile Number')->required()->columnSpanFull(),
            TextInput::make('customer_name')->label('Customer Name'),
            TextInput::make('alt_mobile_number')->label('Alternate Mobile'),
            Textarea::make('address')->label('Address')->columnSpanFull(),
            TextInput::make('district')->label('District'),

            // Product — Walton exact values dropdown
            Select::make('product_name')
                ->label('Product Name')
                ->options($waltonProducts)
                ->searchable()
                ->allowHtml(false)
                ->helperText('Walton API accepted product values'),

            TextInput::make('product_model')->label('Product Model'),
            TextInput::make('barcode')->label('Barcode/Serial'),
            Textarea::make('problem_description')->label('Problem Description')->rows(3)->columnSpanFull(),

            // Service Center — cached dropdown (VPN sync এর পরে ভরবে)
            empty($serviceCenters)
                ? TextInput::make('service_center')
                    ->label('Service Center')
                    ->helperText('VPN connect → Sync Now করলে dropdown আসবে')
                : Select::make('service_center')
                    ->label('Service Center')
                    ->options($serviceCenters)
                    ->searchable()
                    ->helperText('Walton Service Center code'),

            Select::make('warranty')
                ->label('Warranty Status')
                ->options([
                    '1' => '✅ ওয়ারেন্টি আছে',
                    '0' => '❌ ওয়ারেন্টি নেই',
                ])
                ->helperText('Walton: 1 = আছে, 0 = নেই'),

            TextInput::make('brand')->label('Brand')->default('WALTON'),
            \Filament\Forms\Components\Toggle::make('is_emergency')
                ->label('🚨 Emergency SR')
                ->helperText('টেকনিশিয়ান ৩+ দিন আসেননি — Walton API তে CALL_TYPE=EMERGENCY পাঠানো হবে')
                ->inline(false),
            Select::make('status')->label('Status')->options([
                'Incoming'             => 'Incoming',
                'Pending'              => 'Pending',
                'In Progress'          => 'In Progress',
                'Resolved'             => 'Resolved',
                'Escalation Requested' => 'Escalation Requested',
                'Drop Call'            => 'Drop Call',
                'Rejected'             => 'Rejected',
            ])->required(),
            Textarea::make('comments')->label('Comments')->rows(4)->columnSpanFull(),
            Textarea::make('call_transcript')->label('Call Transcript')->rows(5)->columnSpanFull(),
            TextInput::make('client_sr_id')
                ->label('Client SR ID (API Sync)')
                ->helperText('Client database-এ যে SR ID — API connect হলে auto-fill হবে')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('customer_name')->label('Name')
                    ->searchable()->default('N/A')
                    ->description(fn ($r) => ($r?->mobile_number) ? "📱 {$r->mobile_number}" : null),
                TextColumn::make('product_name')->label('Product')->searchable()->default('-'),
                TextColumn::make('problem_description')->label('Problem')->limit(60)->wrap()->default('-'),
                TextColumn::make('district')->label('District')->default('-'),
                \Filament\Tables\Columns\IconColumn::make('is_emergency')
                    ->label('🚨')
                    ->boolean()
                    ->trueIcon('heroicon-s-fire')
                    ->falseIcon(null)
                    ->trueColor('danger')
                    ->tooltip('Emergency SR — টেকনিশিয়ান অনুপস্থিত')
                    ->width('40px'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (?string $s): string => match($s) {
                        'Resolved'             => 'success',
                        'Pending','In Progress'=> 'warning',
                        'Drop Call','Rejected' => 'danger',
                        'Escalation Requested' => 'purple',
                        default                => 'gray',
                    }),
                // ── Client DB Sync columns ─────────────────────────────────
                TextColumn::make('client_ticket_id')
                    ->label('Client SR ID')
                    ->default('—')
                    ->color('info')
                    ->icon(fn ($record) => $record?->client_ticket_id ? 'heroicon-o-check-circle' : null)
                    ->tooltip('Client database এ এই SR এর ID'),
                TextColumn::make('client_ticket_status')
                    ->label('Client Status')
                    ->default('—')
                    ->badge()
                    ->color(fn (?string $s) => match(strtolower($s ?? '')) {
                        'resolved','completed','done','closed' => 'success',
                        'pending','open','new'                 => 'warning',
                        'cancelled','rejected'                 => 'danger',
                        default                                => 'gray',
                    }),
                TextColumn::make('client_synced_at')
                    ->label('Last Sync')
                    ->since()
                    ->sortable()
                    ->placeholder('—')
                    ->color('gray'),
                // ──────────────────────────────────────────────────────────
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Incoming'=>'Incoming','Pending'=>'Pending','In Progress'=>'In Progress',
                    'Resolved'=>'Resolved','Escalation Requested'=>'Escalation Requested',
                    'Drop Call'=>'Drop Call','Rejected'=>'Rejected',
                ]),
                SelectFilter::make('ivr_service_id')->label('IVR Service')
                    ->options(IvrService::pluck('service_name', 'id')),
                // ── Client sync filter ────────────────────────────────────
                \Filament\Tables\Filters\Filter::make('client_synced')
                    ->label('✅ Client Synced Only')
                    ->query(fn ($query) => $query->whereNotNull('client_ticket_id')),
                \Filament\Tables\Filters\Filter::make('not_synced')
                    ->label('⚠️ Not Synced to Client')
                    ->query(fn ($query) => $query->whereNull('client_ticket_id')
                        ->whereNotIn('status', ['Incoming','Drop Call'])),
                \Filament\Tables\Filters\Filter::make('emergency_only')
                    ->label('🚨 Emergency Only')
                    ->query(fn ($query) => $query->where('is_emergency', true)),
                // ──────────────────────────────────────────────────────────
            ])
            ->actions([
                EditAction::make(),

                \Filament\Actions\Action::make('walton_status')
                    ->label('🔍 Walton Status')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn (\App\Models\SrTicket $record) => !empty($record->client_ticket_id))
                    ->modalHeading(fn (\App\Models\SrTicket $r) => "Walton SR: {$r->client_ticket_id}")
                    ->modalContent(function (\App\Models\SrTicket $record) {
                        $sr = \App\Services\ClientApiPushService::lookupWaltonSr($record);
                        if (!$sr) {
                            return new \Illuminate\Support\HtmlString(
                                '<p class="text-red-500">Walton API থেকে SR পাওয়া যায়নি।</p>'
                            );
                        }
                        $rows = '';
                        $labels = [
                            'SERVICE_NO'           => 'SR নম্বর',
                            'CUST_NAME'            => 'কাস্টমার নাম',
                            'CUST_MOBILE'          => 'মোবাইল',
                            'PRODUCT'              => 'পণ্য',
                            'ITEM_NAME'            => 'Item Name',
                            'MODEL'                => 'মডেল',
                            'SERVICE_CENTER'       => 'সার্ভিস সেন্টার',
                            'WARRANTY_STATUS'      => 'ওয়ারেন্টি',
                            'SERVICE_STATUS'       => 'SR স্ট্যাটাস',
                            'PROBLEMS'             => 'সমস্যা',
                            'COMPLAIN_TYPE'        => 'Complain Type',
                            'CREATED_DATE'         => 'তৈরির তারিখ',
                            'DELIVERY_DATE'        => 'ডেলিভারি তারিখ',
                            'BILL_STATUS'          => 'বিল স্ট্যাটাস',
                        ];
                        foreach ($labels as $key => $label) {
                            $val = $sr[$key] ?? '—';
                            $rows .= "<tr><td class='font-semibold pr-4 py-1 text-gray-600'>{$label}</td><td class='py-1'>{$val}</td></tr>";
                        }
                        return new \Illuminate\Support\HtmlString(
                            "<table class='w-full text-sm'>{$rows}</table>"
                        );
                    })
                    ->modalSubmitAction(false),

                \Filament\Actions\Action::make('send_sms')
                    ->label('📨 SMS')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn (\App\Models\SrTicket $r) => !empty($r->mobile_number))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('mobile')
                            ->label('মোবাইল নম্বর')
                            ->default(fn (\App\Models\SrTicket $r) => $r->mobile_number)
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('message')
                            ->label('বার্তা')
                            ->rows(4)
                            ->default(fn (\App\Models\SrTicket $r) => $r->client_ticket_id
                                ? "প্রিয় {$r->customer_name}, আপনার ওয়ালটন SR নম্বর: {$r->client_ticket_id} ({$r->product_name})। এই নম্বরটি সেভ করুন। -Walton BD"
                                : "প্রিয় {$r->customer_name}, আপনার সার্ভিস রিকোয়েস্ট নথিভুক্ত হয়েছে। শীঘ্রই একজন সার্ভিস ইঞ্জিনিয়ার যোগাযোগ করবেন। -Walton BD"
                            )
                            ->required(),
                    ])
                    ->action(function (\App\Models\SrTicket $record, array $data) {
                        $result = \App\Services\SmsService::sendManual(
                            $data['mobile'],
                            $data['message'],
                            'sr_ticket',
                            $record->id,
                            'admin'
                        );
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()->title('✅ SMS পাঠানো হয়েছে!')->success()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title('❌ ' . $result['message'])->danger()->send();
                        }
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSrTickets::route('/'),
            'create' => Pages\CreateSrTicket::route('/create'),
            'edit'   => Pages\EditSrTicket::route('/{record}/edit'),
        ];
    }
}