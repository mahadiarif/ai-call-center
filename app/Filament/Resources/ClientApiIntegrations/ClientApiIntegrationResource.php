<?php

namespace App\Filament\Resources\ClientApiIntegrations;

use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;

class ClientApiIntegrationResource extends Resource
{
    protected static ?string $model = ClientApiIntegration::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-link';
    protected static string | \UnitEnum | null $navigationGroup = '🛡️ System Management';
    protected static ?string $navigationLabel = 'Client API Settings';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Tabs::make('API Config')
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('১. সংযোগ তথ্য')->schema([
                        \Filament\Forms\Components\Select::make('company_profile_id')
                            ->relationship('company', 'company_name')
                            ->required()
                            ->label('কোম্পানি'),

                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('API এর নাম')
                            ->placeholder('যেমন: Walton ERP, Walton CRM'),

                        \Filament\Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->label('বিবরণ (ঐচ্ছিক)')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\TextInput::make('api_base_url')
                            ->required()
                            ->url()
                            ->label('API Base URL')
                            ->placeholder('https://api.yourcompany.com'),

                        \Filament\Forms\Components\Select::make('auth_type')
                            ->options([
                                'api_key' => 'API Key (Custom Header)',
                                'bearer'  => 'Bearer Token',
                                'basic'   => 'Basic Auth (Username + Password)',
                                'none'    => 'No Authentication',
                            ])
                            ->default('api_key')
                            ->live()
                            ->label('Authentication পদ্ধতি'),

                        \Filament\Forms\Components\TextInput::make('auth_header_name')
                            ->label('Header নাম')
                            ->placeholder('X-API-Key')
                            ->helperText('auth_type = api_key হলে এই header-এ key পাঠাবে')
                            ->visible(fn ($get) => $get('auth_type') === 'api_key'),

                        \Filament\Forms\Components\TextInput::make('api_key')
                            ->password()
                            ->revealable()
                            ->label('API Key / Token / Username')
                            ->visible(fn ($get) => $get('auth_type') !== 'none'),

                        \Filament\Forms\Components\TextInput::make('api_secret')
                            ->password()
                            ->revealable()
                            ->label('API Secret / Password')
                            ->helperText('শুধু Basic Auth-এর জন্য')
                            ->visible(fn ($get) => $get('auth_type') === 'basic'),

                        \Filament\Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('চালু রাখবেন?'),

                        \Filament\Forms\Components\TextInput::make('sync_interval_minutes')
                            ->numeric()
                            ->default(60)
                            ->minValue(5)
                            ->label('কতক্ষণ পর পর sync হবে? (মিনিট)')
                            ->helperText('Default: ৬০ মিনিট = ১ ঘণ্টা'),
                    ])->columns(2),

                    \Filament\Schemas\Components\Tabs\Tab::make('২. Sync Endpoints')->schema([
                        \Filament\Forms\Components\Placeholder::make('walton_sync_note')
                            ->label('ℹ️ Walton API — Sync সম্পর্কে')
                            ->content('⚠️ Walton API-তে bulk list endpoint নেই, তাই Sync করলে 0 records আসে — এটা স্বাভাবিক। Walton এর real-time SR lookup Python bridge এ হয় (call আসলে AI নিজেই mobile দিয়ে SR খোঁজে)। SR Create → insert.php | SR Search → webCrmSrSearch.php | Walton এর জন্য এই ট্যাব empty রাখুন।')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Repeater::make('sync_endpoints')
                            ->label('কোন API endpoint থেকে কোন ধরনের data নেবে (Walton এর জন্য empty রাখুন)')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('path')
                                    ->required()
                                    ->label('Endpoint Path')
                                    ->placeholder('/api/v1/customers'),

                                \Filament\Forms\Components\Select::make('data_type')
                                    ->required()
                                    ->options([
                                        'customer'       => 'Customer / কাস্টমার তথ্য',
                                        'sr_history'     => 'SR History / পুরানো সার্ভিস রিকোয়েস্ট',
                                        'product'        => 'Product / পণ্য তালিকা',
                                        'service_center' => 'Service Center / সার্ভিস সেন্টার',
                                        'technician'     => 'Technician / মেকানিক তালিকা',
                                        'district'       => 'District / জেলা তালিকা',
                                        'other'          => 'Other / অন্য',
                                    ])
                                    ->label('Data এর ধরন'),

                                \Filament\Forms\Components\TextInput::make('id_field')
                                    ->default('id')
                                    ->label('ID Field নাম')
                                    ->helperText('প্রতিটা record-এর unique ID field'),

                                \Filament\Forms\Components\TextInput::make('phone_field')
                                    ->default('mobile')
                                    ->label('Phone Field নাম')
                                    ->helperText('Customer mobile number field name'),

                                \Filament\Forms\Components\TextInput::make('barcode_field')
                                    ->label('Barcode Field নাম (optional)')
                                    ->helperText('Product barcode field name, if any'),

                                \Filament\Forms\Components\TextInput::make('data_path')
                                    ->label('Data Path (optional)')
                                    ->placeholder('data.records')
                                    ->helperText('JSON response-এ data কোথায় আছে? যেমন: data.records বা results'),

                                \Filament\Forms\Components\Toggle::make('paginate')
                                    ->default(false)
                                    ->label('Pagination আছে?'),

                                \Filament\Forms\Components\TextInput::make('page_param')
                                    ->default('page')
                                    ->label('Page Parameter নাম')
                                    ->visible(fn ($get) => $get('paginate')),

                                \Filament\Forms\Components\TextInput::make('per_page')
                                    ->numeric()
                                    ->default(100)
                                    ->label('Per Page')
                                    ->visible(fn ($get) => $get('paginate')),
                            ])
                            ->columns(2)
                            ->addActionLabel('➕ নতুন Endpoint যোগ করুন')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('৩. Outbound — আমরা Client কে Push করবো')->schema([
                        \Filament\Forms\Components\Placeholder::make('outbound_info')
                            ->label('ℹ️ কীভাবে কাজ করে')
                            ->content('এখানে client এর API endpoint গুলো দিন। SR বা QM টিকেট তৈরি হলে AI automatically সেটা client এর database এ পাঠিয়ে দেবে।')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\KeyValue::make('outbound_endpoints')
                            ->label('Client এর API Endpoints (Push করার জন্য)')
                            ->keyLabel('Endpoint নাম')
                            ->valueLabel('URL বা Path (যেমন: /api/sr/create)')
                            ->helperText('Keys: sr_create | qm_complaint_create | qm_parts_create | qm_bill_create | ticket_update')
                            ->addActionLabel('➕ Endpoint যোগ করুন')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Placeholder::make('field_mapping_info')
                            ->label('📋 Field Mapping (Optional)')
                            ->content('যদি client এর field name আমাদের থেকে আলাদা হয়, তাহলে নিচে mapping দিন।')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Textarea::make('field_mapping')
                            ->label('Field Mapping (JSON format)')
                            ->placeholder('{"sr":{"mobile_number":"phone","customer_name":"name"},"global":{}}')
                            ->helperText('JSON format — sr/qm_complaint/qm_parts/qm_bill/global key এর নিচে আমাদের field→client field mapping')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('৪. Webhook (Client → আমরা)')->schema([
                        \Filament\Forms\Components\Placeholder::make('webhook_info')
                            ->label('Webhook URL — Client এই URL এ data পাঠাবে')
                            ->content(fn ($record) => $record
                                ? url("/api/client-webhook/{$record->id}")
                                : '(Save করার পরে URL দেখাবে)')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\TextInput::make('webhook_secret')
                            ->password()
                            ->revealable()
                            ->label('Webhook Secret (optional)')
                            ->helperText('Client এই secret দিয়ে X-Webhook-Signature header পাঠাবে — verification এর জন্য')
                            ->columnSpanFull(),
                    ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('৫. Discovered Schema')->schema([
                        \Filament\Forms\Components\Placeholder::make('schema_info')
                            ->label('ℹ️ Auto-Discover করার পরে এখানে client এর fields দেখাবে')
                            ->content('🔍 Auto-Discover button চাপলে AI client API থেকে SR ও QM field structure বের করে এখানে save করে। তারপর থেকে AI সেই fields follow করে call collect করে।')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Placeholder::make('sr_fields_preview')
                            ->label('📋 SR Fields (Discovered)')
                            ->content(fn ($record) => $record && !empty($record->discovered_schema['sample_sr_fields'])
                                ? implode(' | ', array_map(
                                    fn($f) => "{$f} (" . \App\Models\ClientApiIntegration::translateField($f) . ")",
                                    $record->discovered_schema['sample_sr_fields']
                                  ))
                                : '(Auto-Discover করা হয়নি)')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Placeholder::make('qm_fields_preview')
                            ->label('📋 QM Fields (Discovered)')
                            ->content(fn ($record) => $record && !empty($record->discovered_schema['sample_qm_fields'])
                                ? implode(' | ', array_map(
                                    fn($f) => "{$f} (" . \App\Models\ClientApiIntegration::translateField($f) . ")",
                                    $record->discovered_schema['sample_qm_fields']
                                  ))
                                : '(Auto-Discover করা হয়নি)')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Placeholder::make('last_discovered')
                            ->label('⏰ শেষ Discovery')
                            ->content(fn ($record) => $record?->last_discovered_at
                                ? $record->last_discovered_at->diffForHumans()
                                : 'কখনো discover করা হয়নি')
                            ->columnSpanFull(),
                    ]),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('API নাম')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.company_name')
                    ->label('কোম্পানি')
                    ->searchable(),
                TextColumn::make('api_base_url')
                    ->label('Base URL')
                    ->limit(40),
                TextColumn::make('sync_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'success' => 'success',
                        'running' => 'warning',
                        'error'   => 'danger',
                        default   => 'gray',
                    }),
                TextColumn::make('last_synced_at')
                    ->label('Last Sync')
                    ->since()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('চালু')
                    ->boolean(),
                TextColumn::make('cacheRecords_count')
                    ->label('Cached Records')
                    ->counts('cacheRecords'),
            ])
            ->actions([
                EditAction::make(),

                // 🔍 Auto-Discover: API structure বুঝে সব endpoint খুঁজে clone করো
                Action::make('auto_discover')
                    ->label('🔍 Auto-Discover')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('API Auto-Discover')
                    ->modalDescription('AI নিজেই client এর API তে গিয়ে SR/QM structure বুঝবে, সব endpoint খুঁজবে এবং সব data clone করবে। এটা কয়েক মিনিট লাগতে পারে।')
                    ->modalSubmitActionLabel('হ্যাঁ, শুরু করো')
                    ->action(function (ClientApiIntegration $record) {
                        try {
                            $record->update(['sync_status' => 'running']);
                            $schema = \App\Services\ClientApiDiscoveryService::discover($record);
                            $total  = \App\Services\ClientApiDiscoveryService::cloneAll($record);

                            $srFound  = count($schema['sr_endpoints'] ?? []);
                            $qmFound  = count($schema['qm_endpoints'] ?? []);
                            $custFound= count($schema['customer_endpoints'] ?? []);

                            \Filament\Notifications\Notification::make()
                                ->title('✅ Discovery সম্পন্ন!')
                                ->body("SR Endpoints: {$srFound} | QM Endpoints: {$qmFound} | Customer: {$custFound} | Cloned Records: {$total}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            $record->update(['sync_status' => 'error', 'last_error' => $e->getMessage()]);
                            \Filament\Notifications\Notification::make()
                                ->title('❌ Discovery ব্যর্থ হয়েছে')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 🔄 Manual Sync — discovered endpoints থেকে fresh data টানো
                Action::make('sync_now')
                    ->label('🔄 Sync Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (ClientApiIntegration $record) {
                        try {
                            $total = \App\Services\ClientApiDiscoveryService::cloneAll($record);
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Sync সম্পন্ন!')
                                ->body("{$total} টি record আপডেট হয়েছে।")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ Sync ব্যর্থ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 🧪 Test SR Push — sr_create endpoint এ dummy data POST করো
                Action::make('test_sr_push')
                    ->label('📤 Test SR Push')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Test SR Create Push')
                    ->modalDescription('এটি Walton API-এর sr_create endpoint এ একটি real-format test payload পাঠাবে।')
                    ->action(function (ClientApiIntegration $record) {
                        $endpoints  = $record->outbound_endpoints ?? [];
                        $createPath = $endpoints['sr_create'] ?? null;

                        if (!$createPath) {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ sr_create endpoint নেই')
                                ->body("Outbound Endpoints section-এ sr_create key add করুন।\nযেমন: sr_create → insert.php")
                                ->danger()->persistent()->send();
                            return;
                        }

                        $createUrl = str_starts_with($createPath, 'http') ? $createPath
                                   : rtrim($record->api_base_url, '/') . '/' . ltrim($createPath, '/');

                        // ── Use real field_mapping (array cast in model) — same as ClientApiPushService::mapFields() ──
                        $rawMapping = $record->field_mapping ?? [];
                        if (is_string($rawMapping)) {
                            $rawMapping = json_decode($rawMapping, true) ?? [];
                        }
                        // Support both wrapped {"sr":{...}} and flat {"customer_name":"CUSTOMER_NAME"} formats
                        $mapping = $rawMapping['sr'] ?? $rawMapping['global'] ?? $rawMapping;
                        if (!is_array($mapping)) $mapping = [];

                        $testFields = [
                            'ticket_type'         => 'SR',
                            'customer_name'       => 'Test Customer AI',
                            'mobile_number'       => '01700000000',
                            'alt_mobile_number'   => '',
                            'address'             => 'Test Address, Dhaka',
                            'district'            => 'Dhaka',
                            'product_name'        => 'REFRIGERATOR',
                            'product_model'       => '',
                            'barcode'             => '',
                            'problem_description' => 'Test SR from AI Call Center admin panel',
                            'service_center'      => '',
                            'brand'               => 'WALTON',
                            'warranty'            => '0',
                            'our_ticket_id'       => 'TEST-0',
                            'created_at'          => now()->toIso8601String(),
                        ];

                        // Step 1: map our keys → client keys
                        $payload = [];
                        foreach ($testFields as $ourKey => $value) {
                            $clientKey = $mapping[$ourKey] ?? null;
                            if ($clientKey && str_starts_with($clientKey, '__static:')) continue;
                            $apiKey = $clientKey ?: strtoupper($ourKey);
                            $payload[$apiKey] = $value;
                        }

                        // Step 2: inject __static fields (username, key, SOURCE, etc.)
                        foreach ($mapping as $fieldName => $directive) {
                            if (is_string($directive) && str_starts_with($directive, '__static:')) {
                                $payload[$fieldName] = substr($directive, 9);
                            }
                        }

                        // Step 3: normalize WARRANTY_STATUS
                        if (isset($payload['WARRANTY_STATUS'])) {
                            $payload['WARRANTY_STATUS'] = '0';
                        }

                        // Remove empty strings (optional cleanup) — DON'T remove, Walton may require empty fields
                        // $payload = array_filter($payload, fn($v) => $v !== '');

                        // ── HTTP — use PATCH + JSON (Walton insert.php format) ──
                        $http = \Illuminate\Support\Facades\Http::asJson()->timeout(10);

                        try {
                            $response = $http->patch($createUrl, $payload);
                            $status   = $response->status();
                            $body     = substr($response->body(), 0, 600);
                            $payloadJson = substr(json_encode($payload, JSON_UNESCAPED_UNICODE), 0, 800);

                            \Illuminate\Support\Facades\Log::info('[TestConnection] Test SR Push', [
                                'url'     => $createUrl,
                                'status'  => $status,
                                'payload' => $payload,
                                'body'    => $body,
                            ]);

                            $icon = $response->successful() ? '✅' : '⚠️';
                            \Filament\Notifications\Notification::make()
                                ->title("{$icon} Test SR Push — HTTP {$status}")
                                ->body("URL: {$createUrl}\n\nPayload:\n{$payloadJson}\n\nResponse:\n{$body}")
                                ->info()->persistent()->send();

                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ SR Push ব্যর্থ — Connection Error')
                                ->body($e->getMessage())
                                ->danger()->persistent()->send();
                        }
                    }),

                // 🧪 Test Connection — VPN দিয়ে Walton API reach হচ্ছে?
                Action::make('test_connection')
                    ->label('🧪 Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (ClientApiIntegration $record) {
                        $base    = rtrim($record->api_base_url, '/');
                        $results = [];

                        // Step 1: Base URL reachability
                        try {
                            $r = \Illuminate\Support\Facades\Http::timeout(5)->get($base . '/');
                            $results[] = "✅ Base URL reachable — HTTP {$r->status()}";
                        } catch (\Throwable $e) {
                            $results[] = "❌ Base URL NOT reachable: " . $e->getMessage();
                        }

                        // Step 2: SR Search endpoint test
                        $endpoints  = $record->outbound_endpoints ?? [];
                        $searchPath = $endpoints['sr_search'] ?? 'webCrmSrSearch.php';
                        $searchUrl  = str_starts_with($searchPath, 'http') ? $searchPath
                                    : $base . '/' . ltrim($searchPath, '/');
                        try {
                            $r = \Illuminate\Support\Facades\Http::asForm()->timeout(8)->post($searchUrl, [
                                'username'        => 'walton',
                                'key'             => 'xHj0LoH!9%4VVWYWQilrti',
                                'CUSTOMER_MOBILE' => '01700000000',
                            ]);
                            $body = substr($r->body(), 0, 300);
                            $results[] = "✅ SR Search ({$searchPath}) — HTTP {$r->status()} | Response: {$body}";
                        } catch (\Throwable $e) {
                            $results[] = "❌ SR Search FAILED: " . $e->getMessage();
                        }

                        // Step 3: SR Create endpoint test (just ping, no actual create)
                        $createPath = $endpoints['sr_create'] ?? 'insert.php';
                        $createUrl  = str_starts_with($createPath, 'http') ? $createPath
                                    : $base . '/' . ltrim($createPath, '/');
                        try {
                            $r = \Illuminate\Support\Facades\Http::timeout(5)->get($createUrl);
                            $results[] = "✅ SR Create URL reachable ({$createPath}) — HTTP {$r->status()}";
                        } catch (\Throwable $e) {
                            $results[] = "⚠️ SR Create ({$createPath}) GET ping failed: " . $e->getMessage();
                        }

                        \Illuminate\Support\Facades\Log::info('[TestConnection] Results', $results);

                        \Filament\Notifications\Notification::make()
                            ->title('🧪 Connection Test Results')
                            ->body(implode("\n\n", $results))
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                // 📋 View Recent API Logs
                Action::make('view_logs')
                    ->label('📋 Logs')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(function (ClientApiIntegration $record) {
                        $logFile = storage_path('logs/laravel.log');
                        $lines   = [];
                        if (file_exists($logFile)) {
                            $all = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $filtered = array_filter($all, fn($l) =>
                                str_contains($l, 'ClientApiPush') ||
                                str_contains($l, 'WaltonLookup') ||
                                str_contains($l, 'TestConnection') ||
                                str_contains($l, 'ServiceCenter')
                            );
                            $lines = array_slice(array_values($filtered), -20); // last 20 matching lines
                        }

                        $body = empty($lines)
                            ? 'কোনো API log এখনো নেই। একটা call আসলে বা SR তৈরি হলে এখানে log দেখাবে।'
                            : implode("\n", array_map(fn($l) => substr($l, 0, 200), $lines));

                        \Filament\Notifications\Notification::make()
                            ->title('📋 Recent API Logs (last 20)')
                            ->body($body)
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->emptyStateHeading('কোনো Client API সংযোগ নেই')
            ->emptyStateDescription('Client এর API connect করলে AI তাদের database থেকে তথ্য নিয়ে smart conversation করতে পারবে।');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClientApiIntegrations::route('/'),
            'create' => Pages\CreateClientApiIntegration::route('/create'),
            'edit'   => Pages\EditClientApiIntegration::route('/{record}/edit'),
        ];
    }
}