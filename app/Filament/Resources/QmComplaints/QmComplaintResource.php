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
    protected static bool $shouldRegisterNavigation = false;

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
                    ->description(fn ($r) => ($r?->mobile_number) ? "📱 {$r->mobile_number}" : null),
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
                // ── Client DB Sync ─────────────────────────────────────────
                TextColumn::make('client_ticket_id')
                    ->label('Client QM ID')
                    ->default('—')
                    ->color('info')
                    ->icon(fn ($record) => $record?->client_ticket_id ? 'heroicon-o-check-circle' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client_ticket_status')
                    ->label('Client Status')
                    ->default('—')
                    ->badge()
                    ->color(fn (?string $s) => match(strtolower($s ?? '')) {
                        'resolved','completed','done','closed' => 'success',
                        'pending','open','new'                 => 'warning',
                        'cancelled','rejected'                 => 'danger',
                        default                                => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                // ──────────────────────────────────────────────────────────
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
                // ── Client sync filters ────────────────────────────────────
                \Filament\Tables\Filters\Filter::make('client_synced')
                    ->label('✅ Client Synced Only')
                    ->query(fn ($query) => $query->whereNotNull('client_ticket_id')),
                \Filament\Tables\Filters\Filter::make('not_synced')
                    ->label('⚠️ Not Synced to Client')
                    ->query(fn ($query) => $query->whereNull('client_ticket_id')
                        ->whereNotIn('status', ['Incoming','Drop Call'])),
                // ──────────────────────────────────────────────────────────
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