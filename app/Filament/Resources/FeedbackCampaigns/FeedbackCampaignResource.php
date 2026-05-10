<?php
namespace App\Filament\Resources\FeedbackCampaigns;

use App\Models\FeedbackCampaign;
use App\Models\CompanyProfile;
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

class FeedbackCampaignResource extends Resource
{
    protected static ?string $model = FeedbackCampaign::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowUpRight;
    protected static ?string $navigationLabel = 'Feedback Campaigns';
    protected static ?string $modelLabel = 'Feedback Campaign';
    protected static string|\UnitEnum|null $navigationGroup = 'আউটবাউন্ড সার্ভে';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Campaign নাম')
                ->placeholder('যেমন: মে ২০২৬ SR Feedback')->required()->columnSpanFull(),
            Select::make('company_profile_id')->label('Company')
                ->options(CompanyProfile::where('is_active', true)->pluck('company_name', 'id'))->searchable(),
            Select::make('greeting_company')->label('শুভেচ্ছায় কোম্পানির নাম')
                ->options(['ওয়ালটন'=>'ওয়ালটন','মারসেল'=>'মারসেল','Walton'=>'Walton','Marcel'=>'Marcel'])
                ->default('ওয়ালটন'),
            Select::make('status')->label('Status')
                ->options(['draft'=>'Draft','active'=>'Active','paused'=>'Paused','completed'=>'Completed'])
                ->default('draft')->required(),
            Textarea::make('description')->label('বিবরণ')->rows(2)->columnSpanFull(),
            Textarea::make('custom_script')->label('Custom Script (ঐচ্ছিক)')
                ->helperText('খালি রাখলে default Walton survey script ব্যবহার হবে')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('name')->label('Campaign')->searchable()
                    ->description(fn($r) => $r?->description ? \Str::limit($r->description,60) : null),
                TextColumn::make('greeting_company')->label('Company')->badge()->color('info'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn(?string $s)=>match($s){'active'=>'success','draft'=>'gray','paused'=>'warning','completed'=>'info',default=>'gray'})
                    ->formatStateUsing(fn($s)=>match($s){'active'=>'▶ Active','draft'=>'✏ Draft','paused'=>'⏸ Paused','completed'=>'✅ Completed',default=>$s}),
                TextColumn::make('total_contacts')->label('Contacts')->sortable()
                    ->description(fn($r)=>"কল: ".($r?->called_count ?? 0)." | সম্পন্ন: ".($r?->completed_count ?? 0)),
                TextColumn::make('satisfaction_rate')->label('Satisfaction')
                    ->state(fn($record)=>($r=$record->satisfactionRate())!==null?"{$r}%":'—')
                    ->color(fn($state)=>match(true){
                        is_numeric(rtrim($state??'','%'))&&(float)rtrim($state,'%')>=70=>'success',
                        is_numeric(rtrim($state??'','%'))&&(float)rtrim($state,'%')>=40=>'warning',
                        is_numeric(rtrim($state??'','%'))?'danger':false=>null,
                        default=>'gray',
                    }),
                TextColumn::make('created_at')->label('তৈরি')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('created_at','desc')
            ->filters([
                SelectFilter::make('status')->options(['draft'=>'Draft','active'=>'Active','paused'=>'Paused','completed'=>'Completed']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeedbackCampaigns::route('/'),
            'create' => Pages\CreateFeedbackCampaign::route('/create'),
            'edit'   => Pages\EditFeedbackCampaign::route('/{record}/edit'),
        ];
    }
}