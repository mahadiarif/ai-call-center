<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupClientApiIntegration extends Command
{
    protected $signature   = 'setup:client-api';
    protected $description = 'Client API Integration — Filament Resource, directories, and migration অটোমেটিক তৈরি করো';

    public function handle(): void
    {
        $this->info('🔗 Client API Integration setup শুরু হচ্ছে...');

        // ── 1. Directories ───────────────────────────────────────
        $dirs = [
            app_path('Filament/Resources/ClientApiIntegrations/Pages'),
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->line("  📁 Created: {$dir}");
            }
        }

        // ── 2. Filament Resource files ───────────────────────────
        $this->writeResourceFiles();

        // ── 3. Migrate ───────────────────────────────────────────
        $this->info('');
        $this->call('migrate');
        $this->call('optimize:clear');
        $this->info('');
        $this->info('✅ Client API Integration setup সম্পন্ন!');
        $this->info('');
        $this->info('Admin panel-এ "Client API Settings" menu দেখতে পাবেন।');
        $this->info('');
        $this->info('Webhook URL format:');
        $this->info('  POST /api/client-webhook/{integration_id}');
        $this->info('');
        $this->info('Manual sync:');
        $this->info('  php artisan client:sync --force');
    }

    private function writeResourceFiles(): void
    {
        $base = app_path('Filament/Resources/ClientApiIntegrations');

        // ── Main Resource ────────────────────────────────────────
        $resource = <<<'PHP'
<?php

namespace App\Filament\Resources\ClientApiIntegrations;

use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;

class ClientApiIntegrationResource extends Resource
{
    protected static ?string $model = ClientApiIntegration::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'AI সেটিংস';
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
                        \Filament\Forms\Components\Repeater::make('sync_endpoints')
                            ->label('কোন API endpoint থেকে কোন ধরনের data নেবে')
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

                    \Filament\Schemas\Components\Tabs\Tab::make('৩. Webhook (Push Update)')->schema([
                        \Filament\Forms\Components\Placeholder::make('webhook_info')
                            ->label('Webhook URL')
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
                Action::make('sync_now')
                    ->label('Sync Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (ClientApiIntegration $record) {
                        \Artisan::call('client:sync', [
                            '--company' => $record->company_profile_id,
                            '--force'   => true,
                        ]);
                    })
                    ->successNotificationTitle('Sync শুরু হয়েছে!'),
                Action::make('view_cache')
                    ->label('Cached Data')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.client-api-integrations.edit', $record)),
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
PHP;
        file_put_contents("{$base}/ClientApiIntegrationResource.php", $resource);
        $this->line('  ✅ ClientApiIntegrationResource.php');

        // ── Pages ────────────────────────────────────────────────
        $pages = [
            'ListClientApiIntegrations' => <<<'PHP'
<?php
namespace App\Filament\Resources\ClientApiIntegrations\Pages;
use App\Filament\Resources\ClientApiIntegrations\ClientApiIntegrationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListClientApiIntegrations extends ListRecords {
    protected static string $resource = ClientApiIntegrationResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
PHP,
            'CreateClientApiIntegration' => <<<'PHP'
<?php
namespace App\Filament\Resources\ClientApiIntegrations\Pages;
use App\Filament\Resources\ClientApiIntegrations\ClientApiIntegrationResource;
use Filament\Resources\Pages\CreateRecord;
class CreateClientApiIntegration extends CreateRecord {
    protected static string $resource = ClientApiIntegrationResource::class;
}
PHP,
            'EditClientApiIntegration' => <<<'PHP'
<?php
namespace App\Filament\Resources\ClientApiIntegrations\Pages;
use App\Filament\Resources\ClientApiIntegrations\ClientApiIntegrationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditClientApiIntegration extends EditRecord {
    protected static string $resource = ClientApiIntegrationResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
PHP,
        ];

        foreach ($pages as $class => $content) {
            file_put_contents("{$base}/Pages/{$class}.php", $content);
            $this->line("  ✅ Pages/{$class}.php");
        }
    }
}
