<?php
namespace App\Filament\Resources\QmTickets;

use App\Models\QmTicket;
use App\Filament\Resources\QmTickets\Pages\CreateQmTicket;
use App\Filament\Resources\QmTickets\Pages\EditQmTicket;
use App\Filament\Resources\QmTickets\Pages\ListQmTickets;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class QmTicketResource extends Resource
{
    protected static ?string $model = QmTicket::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $navigationLabel = 'QM Tickets';
    protected static ?string $modelLabel = 'QM Ticket';
    protected static string|\UnitEnum|null $navigationGroup = 'টিকেটস';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Row 1: Customer Name, Mobile, Address ──────────────────────────
            TextInput::make('customer_name')
                ->label('Customer Name')
                ->placeholder('Customer Name'),

            TextInput::make('mobile_number')
                ->label('Customer Mobile')
                ->required()
                ->placeholder('01XXXXXXXXX'),

            TextInput::make('address')
                ->label('Address')
                ->placeholder('Address'),

            // ── Row 2: Query Type, Brand, Product, Related ─────────────────────
            Select::make('query_type')
                ->label('Query Type')
                ->required()
                ->default('General Inquiry')
                ->options([
                    'General Inquiry'   => 'General Inquiry',
                    'Product Inquiry'   => 'Product Inquiry',
                    'Parts Inquiry'     => 'Parts Inquiry',
                    'Bill Inquiry'      => 'Bill Inquiry',
                    'Technical Support' => 'Technical Support',
                    'Complain'          => 'Complain',
                    'Price Inquiry'     => 'Price Inquiry',
                    'Online Sell'       => 'Online Sell',
                    'DCAMP'             => 'DCAMP',
                    'TV Activation'     => 'TV Activation',
                ]),

            Select::make('brand')
                ->label('Brand')
                ->default('WALTON')
                ->options([
                    'WALTON' => 'WALTON',
                    'MARCEL' => 'MARCEL',
                    'ORIGIN' => 'ORIGIN',
                    'SAFE'   => 'SAFE',
                    'OTHERS' => 'OTHERS',
                ]),

            Select::make('product')
                ->label('Product')
                ->searchable()
                ->options([
                    'AC'                  => 'AC',
                    'Refrigerator'        => 'Refrigerator',
                    'Washing Machine'     => 'Washing Machine',
                    'TV'                  => 'TV',
                    'Microwave'           => 'Microwave',
                    'Rice Cooker'         => 'Rice Cooker',
                    'Electric Fan'        => 'Electric Fan',
                    'Blender'             => 'Blender',
                    'Iron'                => 'Iron',
                    'Water Heater'        => 'Water Heater',
                    'Water Purifier'      => 'Water Purifier',
                    'Generator'           => 'Generator',
                    'Laptop'              => 'Laptop',
                    'Mobile'              => 'Mobile',
                    'OTHERS'              => 'OTHERS',
                ]),

            Select::make('related')
                ->label('Related')
                ->default('WSMS')
                ->options([
                    'WSMS'        => 'WSMS',
                    'Call Center' => 'Call Center',
                    'R&D'         => 'R&D',
                    'PLAZA'       => 'PLAZA',
                    'Marketing'   => 'Marketing',
                    'Sourcing'    => 'Sourcing',
                    'Distributor' => 'Distributor',
                    'Admin'       => 'Admin',
                    'Offer'       => 'Offer',
                    'Others'      => 'Others',
                ]),

            // ── Row 3: Subject ─────────────────────────────────────────────────
            Select::make('subject')
                ->label('Subject')
                ->searchable()
                ->options([
                    'Complain Subject'                   => 'Complain Subject',
                    'WSMS Expert Behave'                 => 'WSMS Expert Behave',
                    'Expert Qualification'               => 'Expert Qualification',
                    'Not Get Service In Appropriate Time'=> 'Not Get Service In Appropriate Time',
                    'Not Get Proper Service'             => 'Not Get Proper Service',
                    'Helpline'                           => 'Helpline',
                    'Parts not Available'                => 'Parts not Available',
                    'Product Quality'                    => 'Product Quality',
                    'Product Executive Behave'           => 'Product Executive Behave',
                    'Plaza Executive Behave'             => 'Plaza Executive Behave',
                    'Plaza Executive Not Helpful'        => 'Plaza Executive Not Helpful',
                    'Product Availability'               => 'Product Availability',
                    'Product Operate'                    => 'Product Operate',
                    'WSMS Executive Behave'              => 'WSMS Executive Behave',
                    'WSMS Executive Not Helpful'         => 'WSMS Executive Not Helpful',
                    'Dealer Behave'                      => 'Dealer Behave',
                    'WSMS Call Not Receive'              => 'WSMS Call Not Receive',
                    'PLAZA Call Not Receive'             => 'PLAZA Call Not Receive',
                    'Promotional Offer'                  => 'Promotional Offer',
                    'Product Price'                      => 'Product Price',
                    'Warranty Card Issue'                => 'Warranty Card Issue',
                    'General Query'                      => 'General Query',
                    'Bill Query'                         => 'Bill Query',
                    'Parts Query'                        => 'Parts Query',
                    'Others'                             => 'Others',
                ]),

            // ── Row 4: Send To Mail, Send Via SMS, Send To Group ───────────────
            Select::make('send_to_mail')
                ->label('Send To Mail')
                ->default('No')
                ->options(['No' => 'No', 'Yes' => 'Yes']),

            Select::make('send_via_sms')
                ->label('Send Via SMS')
                ->default('No')
                ->options(['No' => 'No', 'Yes' => 'Yes']),

            Select::make('send_to_group')
                ->label('Send To Group')
                ->options([
                    'Tech Support' => 'Tech Support',
                    'Parts Query'  => 'Parts Query',
                    'Bill Query'   => 'Bill Query',
                    'Complain'     => 'Complain',
                ]),

            // ── Row 5: Status ──────────────────────────────────────────────────
            Select::make('status')
                ->label('Status')
                ->default('Incoming')
                ->required()
                ->options([
                    'Incoming'     => 'Incoming',
                    'Pending'      => 'Pending',
                    'Under Review' => 'Under Review',
                    'Resolved'     => 'Resolved',
                    'Rejected'     => 'Rejected',
                    'Drop Call'    => 'Drop Call',
                ]),

            // ── Full-width fields ──────────────────────────────────────────────
            Textarea::make('message')
                ->label('Message / Remarks')
                ->rows(4)
                ->columnSpanFull(),

            Textarea::make('comments')
                ->label('Comments')
                ->rows(3)
                ->columnSpanFull(),

            Textarea::make('call_transcript')
                ->label('Call Transcript')
                ->rows(5)
                ->columnSpanFull(),

            TextInput::make('qm_number')
                ->label('QM Number')
                ->disabled()
                ->helperText('Auto-generated'),

            TextInput::make('client_qm_id')
                ->label('Client QM ID (API Sync)')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),

                TextColumn::make('qm_number')
                    ->label('QM No')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->default('N/A')
                    ->description(fn ($r) => $r?->mobile_number ? "📱 {$r->mobile_number}" : null),

                TextColumn::make('query_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Complain'          => 'danger',
                        'Parts Inquiry'     => 'warning',
                        'Bill Inquiry'      => 'info',
                        'Technical Support' => 'primary',
                        default             => 'gray',
                    }),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('product')
                    ->label('Product')
                    ->default('-')
                    ->limit(20),

                TextColumn::make('send_to_group')
                    ->label('Group')
                    ->badge()
                    ->color('success')
                    ->default('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Incoming'     => 'info',
                        'Pending'      => 'warning',
                        'Under Review' => 'primary',
                        'Resolved'     => 'success',
                        'Rejected'     => 'danger',
                        'Drop Call'    => 'gray',
                        default        => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('query_type')
                    ->label('Query Type')
                    ->options([
                        'General Inquiry'   => 'General Inquiry',
                        'Product Inquiry'   => 'Product Inquiry',
                        'Parts Inquiry'     => 'Parts Inquiry',
                        'Bill Inquiry'      => 'Bill Inquiry',
                        'Technical Support' => 'Technical Support',
                        'Complain'          => 'Complain',
                        'Price Inquiry'     => 'Price Inquiry',
                        'Online Sell'       => 'Online Sell',
                        'DCAMP'             => 'DCAMP',
                        'TV Activation'     => 'TV Activation',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Incoming'     => 'Incoming',
                        'Pending'      => 'Pending',
                        'Under Review' => 'Under Review',
                        'Resolved'     => 'Resolved',
                        'Rejected'     => 'Rejected',
                        'Drop Call'    => 'Drop Call',
                    ]),

                SelectFilter::make('brand')
                    ->label('Brand')
                    ->options([
                        'WALTON' => 'WALTON',
                        'MARCEL' => 'MARCEL',
                        'ORIGIN' => 'ORIGIN',
                        'SAFE'   => 'SAFE',
                        'OTHERS' => 'OTHERS',
                    ]),
            ])
            ->actions([
                EditAction::make(),

                \Filament\Actions\Action::make('send_sms')
                    ->label('📨 SMS')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn (\App\Models\QmTicket $r) => !empty($r->mobile_number))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('mobile')
                            ->label('মোবাইল নম্বর')
                            ->default(fn (\App\Models\QmTicket $r) => $r->mobile_number)
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('message')
                            ->label('বার্তা')
                            ->rows(4)
                            ->default(fn (\App\Models\QmTicket $r) =>
                                "প্রিয় {$r->customer_name}, আপনার ওয়ালটন QM টিকেট নম্বর: {$r->qm_number} গ্রহণ করা হয়েছে। শীঘ্রই যোগাযোগ করা হবে। -Walton BD"
                            )
                            ->required(),
                    ])
                    ->action(function (\App\Models\QmTicket $record, array $data) {
                        $result = \App\Services\SmsService::sendManual(
                            $data['mobile'],
                            $data['message'],
                            'qm_ticket',
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
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListQmTickets::route('/'),
            'create' => CreateQmTicket::route('/create'),
            'edit'   => EditQmTicket::route('/{record}/edit'),
        ];
    }
}
