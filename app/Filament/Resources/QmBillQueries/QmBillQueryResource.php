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
    protected static bool $shouldRegisterNavigation = false;

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
                    ->description(fn ($r) => ($r?->mobile_number) ? "📱 {$r->mobile_number}" : null),
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
                TextColumn::make('client_ticket_id')->label('Client ID')->default('—')
                    ->color('info')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client_ticket_status')->label('Client Status')->default('—')
                    ->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Incoming'=>'Incoming','Pending'=>'Pending','Under Review'=>'Under Review',
                    'Resolved'=>'Resolved','Rejected'=>'Rejected','Drop Call'=>'Drop Call',
                ]),
                \Filament\Tables\Filters\Filter::make('client_synced')
                    ->label('✅ Client Synced Only')
                    ->query(fn ($query) => $query->whereNotNull('client_ticket_id')),
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