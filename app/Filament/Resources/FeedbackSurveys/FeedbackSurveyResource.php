<?php
namespace App\Filament\Resources\FeedbackSurveys;

use App\Models\FeedbackSurvey;
use App\Models\FeedbackCampaign;
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

class FeedbackSurveyResource extends Resource
{
    protected static ?string $model = FeedbackSurvey::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $navigationLabel = 'Survey Results';
    protected static ?string $modelLabel = 'Survey';
    protected static string|\UnitEnum|null $navigationGroup = 'আউটবাউন্ড সার্ভে';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('campaign_id')->label('Campaign')
                ->options(FeedbackCampaign::pluck('name','id'))->required(),
            TextInput::make('sr_number')->label('SR নম্বর'),
            TextInput::make('customer_name')->label('নাম'),
            TextInput::make('mobile_number')->label('মোবাইল')->required(),
            TextInput::make('product_name')->label('পণ্য'),
            TextInput::make('district')->label('জেলা'),
            Select::make('call_status')->label('Call Status')->options([
                'pending'=>'Pending','calling'=>'Calling','completed'=>'Completed',
                'no_answer'=>'No Answer','dropped'=>'Dropped','callback'=>'Callback',
            ])->default('pending'),
            Select::make('service_received')->label('সার্ভিস পেয়েছেন?')
                ->options(['yes'=>'হ্যাঁ','no'=>'না','dont_know'=>'জানেন না']),
            Select::make('has_problem')->label('সমস্যা আছে?')
                ->options(['yes'=>'হ্যাঁ','no'=>'না']),
            Textarea::make('problem_details')->label('সমস্যার বিবরণ')->rows(3)->columnSpanFull(),
            Select::make('satisfied')->label('সন্তুষ্ট?')
                ->options(['yes'=>'হ্যাঁ','no'=>'না','dont_know'=>'জানেন না']),
            Textarea::make('satisfaction_comment')->label('মন্তব্য')->rows(3)->columnSpanFull(),
            Textarea::make('general_comment')->label('AI Summary')->rows(3)->columnSpanFull(),
            Textarea::make('call_transcript')->label('Call Transcript')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),
                TextColumn::make('campaign.name')->label('Campaign')->searchable()->limit(25),
                TextColumn::make('sr_number')->label('SR No')->default('—')->badge()->color('primary'),
                TextColumn::make('customer_name')->label('নাম')->searchable()->default('N/A')
                    ->description(fn($r)=>"📱 ".($r?->mobile_number ?? '—')),
                TextColumn::make('product_name')->label('পণ্য')->default('-'),
                TextColumn::make('call_status')->label('Call')->badge()
                    ->color(fn(?string $s)=>match($s){
                        'completed'=>'success','calling'=>'info','pending'=>'gray',
                        'no_answer'=>'warning','dropped'=>'danger','callback'=>'warning',default=>'gray',
                    }),
                TextColumn::make('service_received')->label('সার্ভিস?')->badge()
                    ->color(fn($s)=>match($s){'yes'=>'success','no'=>'danger',default=>'gray'})
                    ->formatStateUsing(fn($s)=>match($s){'yes'=>'✅ হ্যাঁ','no'=>'❌ না','dont_know'=>'❓',default=>'—'}),
                TextColumn::make('has_problem')->label('সমস্যা?')->badge()
                    ->color(fn($s)=>match($s){'no'=>'success','yes'=>'danger',default=>'gray'})
                    ->formatStateUsing(fn($s)=>match($s){'yes'=>'⚠️','no'=>'✅ না',default=>'—'}),
                TextColumn::make('satisfied')->label('সন্তুষ্ট?')->badge()
                    ->color(fn($s)=>match($s){'yes'=>'success','no'=>'danger',default=>'gray'})
                    ->formatStateUsing(fn($s)=>match($s){'yes'=>'😊','no'=>'😞','dont_know'=>'🤷',default=>'—'}),
                TextColumn::make('called_at')->label('কলের সময়')->since()->sortable()->placeholder('—'),
            ])
            ->defaultSort('id','desc')
            ->filters([
                SelectFilter::make('campaign_id')->label('Campaign')
                    ->options(FeedbackCampaign::pluck('name','id')),
                SelectFilter::make('call_status')->options([
                    'pending'=>'Pending','completed'=>'Completed','no_answer'=>'No Answer',
                    'calling'=>'Calling','dropped'=>'Dropped',
                ]),
                SelectFilter::make('service_received')->label('সার্ভিস?')
                    ->options(['yes'=>'হ্যাঁ','no'=>'না','dont_know'=>'জানেন না']),
                SelectFilter::make('satisfied')->label('সন্তুষ্ট?')
                    ->options(['yes'=>'হ্যাঁ','no'=>'না','dont_know'=>'জানেন না']),
                SelectFilter::make('has_problem')->label('সমস্যা?')
                    ->options(['yes'=>'হ্যাঁ','no'=>'না']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeedbackSurveys::route('/'),
            'create' => Pages\CreateFeedbackSurvey::route('/create'),
            'edit'   => Pages\EditFeedbackSurvey::route('/{record}/edit'),
        ];
    }
}