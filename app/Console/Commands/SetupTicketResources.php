<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupTicketResources extends Command
{
    protected $signature   = 'setup:ticket-resources';
    protected $description = 'SR, QM Complaint, QM Parts, QM Bill — Filament Resources তৈরি করো';

    public function handle(): void
    {
        $this->info('🎫 Ticket Resource setup শুরু হচ্ছে...');

        // ── Directories তৈরি ──────────────────────────────────────────
        $dirs = [
            app_path('Filament/Resources/SrTickets/Pages'),
            app_path('Filament/Resources/QmComplaints/Pages'),
            app_path('Filament/Resources/QmPartsQueries/Pages'),
            app_path('Filament/Resources/QmBillQueries/Pages'),
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->line("  📁 Created: {$dir}");
            }
        }

        // ── Resource & Page files লেখা ────────────────────────────────
        $this->writeSrTicketFiles();
        $this->writeQmComplaintFiles();
        $this->writeQmPartsQueryFiles();
        $this->writeQmBillQueryFiles();

        $this->info('');
        $this->info('✅ সব Filament Resources তৈরি হয়েছে!');
        $this->info('');
        $this->info('এখন run করুন:');
        $this->info('  php artisan migrate');
        $this->info('  php artisan optimize:clear');
    }

    // ═══════════════════════════════════════════════════════════
    // SR TICKET RESOURCE
    // ═══════════════════════════════════════════════════════════
    private function writeSrTicketFiles(): void
    {
        $base = app_path('Filament/Resources/SrTickets');

        file_put_contents("{$base}/SrTicketResource.php", <<<'PHP'
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
    protected static string|\UnitEnum|null $navigationGroup = 'টিকেটস';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('mobile_number')->label('Mobile Number')->required()->columnSpanFull(),
            TextInput::make('customer_name')->label('Customer Name'),
            TextInput::make('alt_mobile_number')->label('Alternate Mobile'),
            Textarea::make('address')->label('Address')->columnSpanFull(),
            TextInput::make('district')->label('District'),
            TextInput::make('product_name')->label('Product Name'),
            TextInput::make('product_model')->label('Product Model'),
            TextInput::make('barcode')->label('Barcode/Serial'),
            Textarea::make('problem_description')->label('Problem Description')->rows(3)->columnSpanFull(),
            TextInput::make('service_center')->label('Service Center'),
            TextInput::make('brand')->label('Brand')->default('WALTON'),
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
                    ->description(fn ($r) => $r->mobile_number ? "📱 {$r->mobile_number}" : null),
                TextColumn::make('product_name')->label('Product')->searchable()->default('-'),
                TextColumn::make('problem_description')->label('Problem')->limit(60)->wrap()->default('-'),
                TextColumn::make('district')->label('District')->default('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (?string $s): string => match($s) {
                        'Resolved'             => 'success',
                        'Pending','In Progress'=> 'warning',
                        'Drop Call','Rejected' => 'danger',
                        'Escalation Requested' => 'purple',
                        default                => 'gray',
                    }),
                TextColumn::make('client_sr_id')->label('Client SR ID')->default('—')->color('gray'),
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
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
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
PHP
        );

        file_put_contents("{$base}/Pages/ListSrTickets.php", <<<'PHP'
<?php
namespace App\Filament\Resources\SrTickets\Pages;

use App\Filament\Resources\SrTickets\SrTicketResource;
use App\Models\SrTicket;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSrTickets extends ListRecords
{
    protected static string $resource = SrTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ নতুন SR Ticket')];
    }

    public function getSubheading(): ?string
    {
        $total   = SrTicket::count();
        $pending = SrTicket::where('status', 'Pending')->count();
        $today   = SrTicket::whereDate('created_at', today())->count();
        return "Total: {$total} | Pending: {$pending} | Today: {$today}";
    }
}
PHP
        );

        file_put_contents("{$base}/Pages/CreateSrTicket.php", <<<'PHP'
<?php
namespace App\Filament\Resources\SrTickets\Pages;

use App\Filament\Resources\SrTickets\SrTicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSrTicket extends CreateRecord
{
    protected static string $resource = SrTicketResource::class;
}
PHP
        );

        file_put_contents("{$base}/Pages/EditSrTicket.php", <<<'PHP'
<?php
namespace App\Filament\Resources\SrTickets\Pages;

use App\Filament\Resources\SrTickets\SrTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSrTicket extends EditRecord
{
    protected static string $resource = SrTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
PHP
        );

        $this->info('  ✅ SrTicket Resource');
    }

    // ═══════════════════════════════════════════════════════════
    // QM COMPLAINT RESOURCE
    // ═══════════════════════════════════════════════════════════
    private function writeQmComplaintFiles(): void
    {
        $base = app_path('Filament/Resources/QmComplaints');

        file_put_contents("{$base}/QmComplaintResource.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmComplaints;

use App\Models\QmComplaint;
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

class QmComplaintResource extends Resource
{
    protected static ?string $model = QmComplaint::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static ?string $navigationLabel = 'QM Complaints';
    protected static ?string $modelLabel = 'QM Complaint';
    protected static string|\UnitEnum|null $navigationGroup = 'টিকেটস';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('qm_number')->label('QM নম্বর')->disabled()->helperText('Auto-generated'),
            TextInput::make('sr_reference')->label('SR রেফারেন্স নম্বর'),
            TextInput::make('mobile_number')->label('Mobile Number')->required()->columnSpanFull(),
            TextInput::make('customer_name')->label('Customer Name'),
            TextInput::make('alt_mobile_number')->label('Alternate Mobile'),
            Textarea::make('address')->label('Address')->columnSpanFull(),
            TextInput::make('district')->label('District'),
            Select::make('complaint_category')->label('অভিযোগের ধরন')->options([
                'service_expert'  => 'সার্ভিস এক্সপার্ট',
                'showroom'        => 'শো-রুম / প্লাজা',
                'product_quality' => 'পণ্যের মান',
                'billing'         => 'বিল',
                'other'           => 'অন্যান্য',
            ]),
            TextInput::make('person_name')->label('অভিযোগকৃত ব্যক্তি'),
            TextInput::make('showroom_address')->label('শো-রুম / এলাকা'),
            TextInput::make('incident_date')->label('ঘটনার তারিখ'),
            Textarea::make('complaint_details')->label('সম্পূর্ণ অভিযোগ বিবরণ')->rows(5)->columnSpanFull(),
            Select::make('status')->label('Status')->options([
                'Incoming'     => 'Incoming',
                'Pending'      => 'Pending',
                'Under Review' => 'Under Review',
                'Resolved'     => 'Resolved',
                'Rejected'     => 'Rejected',
                'Drop Call'    => 'Drop Call',
            ])->required(),
            Textarea::make('comments')->label('Comments')->rows(4)->columnSpanFull(),
            Textarea::make('call_transcript')->label('Call Transcript')->rows(5)->columnSpanFull(),
            TextInput::make('client_qm_id')->label('Client QM ID (API Sync)')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('qm_number')->label('QM No')->searchable()->badge()->color('danger'),
                TextColumn::make('customer_name')->label('Name')->searchable()->default('N/A')
                    ->description(fn ($r) => $r->mobile_number ? "📱 {$r->mobile_number}" : null),
                TextColumn::make('complaint_category')->label('Category')->badge()
                    ->formatStateUsing(fn ($s) => match($s) {
                        'service_expert'  => 'সার্ভিস এক্সপার্ট',
                        'showroom'        => 'শো-রুম',
                        'product_quality' => 'পণ্যের মান',
                        'billing'         => 'বিল',
                        default           => $s ?? 'অন্যান্য',
                    }),
                TextColumn::make('complaint_details')->label('অভিযোগ')->limit(60)->default('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (?string $s) => match($s) {
                        'Resolved'     => 'success',
                        'Pending'      => 'warning',
                        'Under Review' => 'info',
                        'Rejected','Drop Call' => 'danger',
                        default        => 'gray',
                    }),
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Incoming'=>'Incoming','Pending'=>'Pending','Under Review'=>'Under Review',
                    'Resolved'=>'Resolved','Rejected'=>'Rejected','Drop Call'=>'Drop Call',
                ]),
                SelectFilter::make('complaint_category')->label('Category')->options([
                    'service_expert'=>'সার্ভিস এক্সপার্ট','showroom'=>'শো-রুম',
                    'product_quality'=>'পণ্যের মান','billing'=>'বিল','other'=>'অন্যান্য',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQmComplaints::route('/'),
            'create' => Pages\CreateQmComplaint::route('/create'),
            'edit'   => Pages\EditQmComplaint::route('/{record}/edit'),
        ];
    }
}
PHP
        );

        file_put_contents("{$base}/Pages/ListQmComplaints.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use App\Models\QmComplaint;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQmComplaints extends ListRecords
{
    protected static string $resource = QmComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ নতুন Complaint')];
    }

    public function getSubheading(): ?string
    {
        $total   = QmComplaint::count();
        $pending = QmComplaint::where('status', 'Pending')->count();
        return "Total: {$total} | Pending: {$pending}";
    }
}
PHP
        );

        file_put_contents("{$base}/Pages/CreateQmComplaint.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQmComplaint extends CreateRecord
{
    protected static string $resource = QmComplaintResource::class;
}
PHP
        );

        file_put_contents("{$base}/Pages/EditQmComplaint.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmComplaint extends EditRecord
{
    protected static string $resource = QmComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
PHP
        );

        $this->info('  ✅ QmComplaint Resource');
    }

    // ═══════════════════════════════════════════════════════════
    // QM PARTS QUERY RESOURCE
    // ═══════════════════════════════════════════════════════════
    private function writeQmPartsQueryFiles(): void
    {
        $base = app_path('Filament/Resources/QmPartsQueries');

        file_put_contents("{$base}/QmPartsQueryResource.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmPartsQueries;

use App\Models\QmPartsQuery;
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

class QmPartsQueryResource extends Resource
{
    protected static ?string $model = QmPartsQuery::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'QM Parts Queries';
    protected static ?string $modelLabel = 'QM Parts Query';
    protected static string|\UnitEnum|null $navigationGroup = 'টিকেটস';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('qm_number')->label('QM নম্বর')->disabled(),
            TextInput::make('mobile_number')->label('Mobile Number')->required(),
            TextInput::make('customer_name')->label('Customer Name'),
            TextInput::make('alt_mobile_number')->label('Alternate Mobile'),
            Textarea::make('address')->label('Address')->columnSpanFull(),
            TextInput::make('district')->label('District'),
            TextInput::make('product_name')->label('পণ্যের নাম'),
            TextInput::make('product_model')->label('পণ্যের মডেল'),
            Textarea::make('parts_name')->label('কোন পার্টস দরকার')->rows(3)->columnSpanFull(),
            TextInput::make('preferred_service_point')->label('পছন্দের সার্ভিস পয়েন্ট'),
            Select::make('status')->label('Status')->options([
                'Incoming'   => 'Incoming',
                'Pending'    => 'Pending',
                'Processing' => 'Processing',
                'Resolved'   => 'Resolved',
                'Rejected'   => 'Rejected',
                'Drop Call'  => 'Drop Call',
            ])->required(),
            Textarea::make('comments')->label('Comments')->rows(3)->columnSpanFull(),
            Textarea::make('call_transcript')->label('Call Transcript')->rows(4)->columnSpanFull(),
            TextInput::make('client_qm_id')->label('Client QM ID (API Sync)')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('qm_number')->label('QM No')->badge()->color('info'),
                TextColumn::make('customer_name')->label('Name')->searchable()->default('N/A')
                    ->description(fn ($r) => $r->mobile_number ? "📱 {$r->mobile_number}" : null),
                TextColumn::make('product_name')->label('Product')->default('-'),
                TextColumn::make('parts_name')->label('Parts')->limit(60)->default('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (?string $s) => match($s) {
                        'Resolved'   => 'success',
                        'Pending'    => 'warning',
                        'Processing' => 'info',
                        'Rejected','Drop Call' => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Incoming'=>'Incoming','Pending'=>'Pending','Processing'=>'Processing',
                    'Resolved'=>'Resolved','Rejected'=>'Rejected','Drop Call'=>'Drop Call',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQmPartsQueries::route('/'),
            'create' => Pages\CreateQmPartsQuery::route('/create'),
            'edit'   => Pages\EditQmPartsQuery::route('/{record}/edit'),
        ];
    }
}
PHP
        );

        file_put_contents("{$base}/Pages/ListQmPartsQueries.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmPartsQueries\Pages;

use App\Filament\Resources\QmPartsQueries\QmPartsQueryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQmPartsQueries extends ListRecords
{
    protected static string $resource = QmPartsQueryResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
PHP
        );

        file_put_contents("{$base}/Pages/CreateQmPartsQuery.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmPartsQueries\Pages;

use App\Filament\Resources\QmPartsQueries\QmPartsQueryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQmPartsQuery extends CreateRecord
{
    protected static string $resource = QmPartsQueryResource::class;
}
PHP
        );

        file_put_contents("{$base}/Pages/EditQmPartsQuery.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmPartsQueries\Pages;

use App\Filament\Resources\QmPartsQueries\QmPartsQueryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmPartsQuery extends EditRecord
{
    protected static string $resource = QmPartsQueryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
PHP
        );

        $this->info('  ✅ QmPartsQuery Resource');
    }

    // ═══════════════════════════════════════════════════════════
    // QM BILL QUERY RESOURCE
    // ═══════════════════════════════════════════════════════════
    private function writeQmBillQueryFiles(): void
    {
        $base = app_path('Filament/Resources/QmBillQueries');

        file_put_contents("{$base}/QmBillQueryResource.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmBillQueries;

use App\Models\QmBillQuery;
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

class QmBillQueryResource extends Resource
{
    protected static ?string $model = QmBillQuery::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'QM Bill Queries';
    protected static ?string $modelLabel = 'QM Bill Query';
    protected static string|\UnitEnum|null $navigationGroup = 'টিকেটস';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('qm_number')->label('QM নম্বর')->disabled(),
            TextInput::make('sr_reference')->label('SR রেফারেন্স নম্বর (কোন SR-এর বিল)'),
            TextInput::make('mobile_number')->label('Mobile Number')->required()->columnSpanFull(),
            TextInput::make('customer_name')->label('Customer Name'),
            TextInput::make('alt_mobile_number')->label('Alternate Mobile'),
            Textarea::make('address')->label('Address')->columnSpanFull(),
            TextInput::make('district')->label('District'),
            TextInput::make('product_name')->label('পণ্যের নাম'),
            Textarea::make('bill_query_details')->label('বিল সংক্রান্ত প্রশ্ন / মন্তব্য')->rows(4)->columnSpanFull(),
            Select::make('status')->label('Status')->options([
                'Incoming'     => 'Incoming',
                'Pending'      => 'Pending',
                'Under Review' => 'Under Review',
                'Resolved'     => 'Resolved',
                'Rejected'     => 'Rejected',
                'Drop Call'    => 'Drop Call',
            ])->required(),
            Textarea::make('comments')->label('Comments')->rows(3)->columnSpanFull(),
            Textarea::make('call_transcript')->label('Call Transcript')->rows(4)->columnSpanFull(),
            TextInput::make('client_qm_id')->label('Client QM ID (API Sync)')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('qm_number')->label('QM No')->badge()->color('warning'),
                TextColumn::make('customer_name')->label('Name')->searchable()->default('N/A')
                    ->description(fn ($r) => $r->mobile_number ? "📱 {$r->mobile_number}" : null),
                TextColumn::make('sr_reference')->label('SR Ref')->default('—'),
                TextColumn::make('bill_query_details')->label('বিল প্রশ্ন')->limit(70)->default('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (?string $s) => match($s) {
                        'Resolved'     => 'success',
                        'Pending'      => 'warning',
                        'Under Review' => 'info',
                        'Rejected','Drop Call' => 'danger',
                        default        => 'gray',
                    }),
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Incoming'=>'Incoming','Pending'=>'Pending','Under Review'=>'Under Review',
                    'Resolved'=>'Resolved','Rejected'=>'Rejected','Drop Call'=>'Drop Call',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQmBillQueries::route('/'),
            'create' => Pages\CreateQmBillQuery::route('/create'),
            'edit'   => Pages\EditQmBillQuery::route('/{record}/edit'),
        ];
    }
}
PHP
        );

        file_put_contents("{$base}/Pages/ListQmBillQueries.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmBillQueries\Pages;

use App\Filament\Resources\QmBillQueries\QmBillQueryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQmBillQueries extends ListRecords
{
    protected static string $resource = QmBillQueryResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
PHP
        );

        file_put_contents("{$base}/Pages/CreateQmBillQuery.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmBillQueries\Pages;

use App\Filament\Resources\QmBillQueries\QmBillQueryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQmBillQuery extends CreateRecord
{
    protected static string $resource = QmBillQueryResource::class;
}
PHP
        );

        file_put_contents("{$base}/Pages/EditQmBillQuery.php", <<<'PHP'
<?php
namespace App\Filament\Resources\QmBillQueries\Pages;

use App\Filament\Resources\QmBillQueries\QmBillQueryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmBillQuery extends EditRecord
{
    protected static string $resource = QmBillQueryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
PHP
        );

        $this->info('  ✅ QmBillQuery Resource');
    }
}
