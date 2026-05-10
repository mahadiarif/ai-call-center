<?php
/**
 * Run once: php setup_feedback_module.php
 * Creates all Filament files for the Feedback Survey module.
 */
$base = __DIR__;

$dirs = [
    "$base/app/Filament/Resources/FeedbackCampaigns",
    "$base/app/Filament/Resources/FeedbackCampaigns/Pages",
    "$base/app/Filament/Resources/FeedbackSurveys",
    "$base/app/Filament/Resources/FeedbackSurveys/Pages",
];
echo "=== Creating Directories ===\n";
foreach ($dirs as $dir) {
    if (!is_dir($dir)) { mkdir($dir, 0755, true); echo "✓ Created: ".str_replace($base,'',$dir)."\n"; }
    else echo "✓ Exists:  ".str_replace($base,'',$dir)."\n";
}

$files = [];

// ── FeedbackCampaignResource.php ─────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackCampaigns/FeedbackCampaignResource.php"] = <<<'PHP'
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
                    ->description(fn($r) => $r->description ? \Str::limit($r->description,60) : null),
                TextColumn::make('greeting_company')->label('Company')->badge()->color('info'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn(?string $s)=>match($s){'active'=>'success','draft'=>'gray','paused'=>'warning','completed'=>'info',default=>'gray'})
                    ->formatStateUsing(fn($s)=>match($s){'active'=>'▶ Active','draft'=>'✏ Draft','paused'=>'⏸ Paused','completed'=>'✅ Completed',default=>$s}),
                TextColumn::make('total_contacts')->label('Contacts')->sortable()
                    ->description(fn($r)=>"কল: {$r->called_count} | সম্পন্ন: {$r->completed_count}"),
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
PHP;

// ── ListFeedbackCampaigns.php ─────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackCampaigns/Pages/ListFeedbackCampaigns.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackCampaigns\Pages;

use App\Filament\Resources\FeedbackCampaigns\FeedbackCampaignResource;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackSurvey;
use App\Models\ClientApiIntegration;
use App\Services\AsteriskAmiService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListFeedbackCampaigns extends ListRecords
{
    protected static string $resource = FeedbackCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ নতুন Campaign'),

            // ── ▶ Start Calling ───────────────────────────────────────────────
            Actions\Action::make('start_calling')
                ->label('▶ Start Calling')
                ->color('success')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Select::make('campaign_id')
                        ->label('Campaign বেছে নিন')
                        ->options(FeedbackCampaign::where('status','active')->pluck('name','id'))
                        ->required()->searchable(),
                    \Filament\Forms\Components\TextInput::make('trunk')
                        ->label('SIP Trunk নাম')
                        ->default(env('ASTERISK_OUTBOUND_TRUNK','SIP/trunk'))
                        ->helperText('আপনার Asterisk এ যে trunk configure করা — যেমন: SIP/gp-trunk বা DAHDI/g1'),
                    \Filament\Forms\Components\TextInput::make('batch_size')
                        ->label('একসাথে কতটি কল (batch)')
                        ->numeric()->default(5)->minValue(1)->maxValue(20)
                        ->helperText('একসাথে max কয়টি কল দেওয়া হবে'),
                ])
                ->modalHeading('Outbound Survey Call শুরু করুন')
                ->modalDescription('এই Campaign এর pending contacts দের কল দেওয়া শুরু হবে। Asterisk AMI দিয়ে কল হবে।')
                ->modalSubmitActionLabel('▶ শুরু করো')
                ->action(function (array $data) {
                    $campaign = FeedbackCampaign::find($data['campaign_id']);
                    if (!$campaign) { Notification::make()->title('Campaign পাওয়া যায়নি')->danger()->send(); return; }

                    $pending = FeedbackSurvey::where('campaign_id', $campaign->id)
                        ->where('call_status','pending')
                        ->limit((int)($data['batch_size']??5))
                        ->get();

                    if ($pending->isEmpty()) {
                        Notification::make()->title('এই Campaign এ আর কোনো pending contact নেই')->warning()->send();
                        return;
                    }

                    $ami     = new AsteriskAmiService();
                    $success = 0;
                    $failed  = 0;
                    $trunk   = $data['trunk'] ?? env('ASTERISK_OUTBOUND_TRUNK','SIP/trunk');

                    foreach ($pending as $survey) {
                        $result = $ami->originateSurveyCall($survey->mobile_number, $survey->id, $trunk);
                        if ($result['success']) {
                            $survey->update(['call_status'=>'calling','called_at'=>now()]);
                            $success++;
                        } else {
                            \Log::warning("[OutboundSurvey] {$survey->mobile_number}: ".$result['message']);
                            $failed++;
                        }
                    }

                    $campaign->syncCounts();

                    Notification::make()
                        ->title("📞 {$success}টি কল শুরু হয়েছে" . ($failed>0 ? " | {$failed}টি fail" : ''))
                        ->color($failed>0 ? 'warning' : 'success')
                        ->send();
                }),

            // ── 📥 Excel/CSV Import ───────────────────────────────────────────
            Actions\Action::make('import_contacts')
                ->label('📥 CSV Import')
                ->color('warning')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\Select::make('campaign_id')
                        ->label('Campaign')
                        ->options(FeedbackCampaign::whereIn('status',['draft','active'])->pluck('name','id'))
                        ->required()->searchable(),
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('CSV ফাইল')
                        ->acceptedFileTypes(['text/csv','text/plain','application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')->directory('survey-imports')
                        ->helperText('Column: mobile_number, customer_name, sr_number, product_name, district, service_date')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $campaign = FeedbackCampaign::find($data['campaign_id']);
                    if (!$campaign) return;
                    $filePath = storage_path('app/'.$data['csv_file']);
                    $imported = 0;
                    if (($h = fopen($filePath,'r')) !== false) {
                        $first = true;
                        while (($row = fgetcsv($h)) !== false) {
                            if ($first) {
                                $first = false;
                                if (!preg_match('/^01[3-9]/',trim($row[0]??''))) continue;
                            }
                            $mobile = trim($row[0]??'');
                            if (empty($mobile)) continue;
                            FeedbackSurvey::firstOrCreate(
                                ['campaign_id'=>$campaign->id,'mobile_number'=>$mobile],
                                ['customer_name'=>trim($row[1]??''),'sr_number'=>trim($row[2]??''),
                                 'product_name'=>trim($row[3]??''),'district'=>trim($row[4]??''),
                                 'service_date'=>!empty($row[5])?trim($row[5]):null,'call_status'=>'pending']
                            );
                            $imported++;
                        }
                        fclose($h);
                    }
                    $campaign->syncCounts();
                    @unlink($filePath);
                    Notification::make()->title("✅ {$imported} contacts import হয়েছে")->success()->send();
                }),

            // ── 🔄 Client API Import ──────────────────────────────────────────
            Actions\Action::make('pull_from_api')
                ->label('🔄 Client API Import')
                ->color('info')
                ->icon('heroicon-o-cloud-arrow-down')
                ->form([
                    \Filament\Forms\Components\Select::make('campaign_id')
                        ->label('Campaign')
                        ->options(FeedbackCampaign::whereIn('status',['draft','active'])->pluck('name','id'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('integration_id')
                        ->label('Client API Integration')
                        ->options(ClientApiIntegration::where('is_active',true)->pluck('name','id'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('limit')
                        ->label('Max records')->numeric()->default(200),
                ])
                ->action(function (array $data) {
                    $campaign    = FeedbackCampaign::find($data['campaign_id']);
                    $integration = ClientApiIntegration::find($data['integration_id']);
                    if (!$campaign||!$integration) return;
                    $records  = \App\Models\ClientDataCache::where('integration_id',$integration->id)
                        ->where('data_type','sr_history')->limit((int)($data['limit']??200))->get();
                    $imported = 0;
                    foreach ($records as $rec) {
                        $d      = $rec->data??[];
                        $mobile = $d['mobile']??$d['mobile_number']??$d['phone']??$rec->search_key??'';
                        if (empty($mobile)) continue;
                        FeedbackSurvey::firstOrCreate(
                            ['campaign_id'=>$campaign->id,'mobile_number'=>$mobile],
                            ['customer_name'=>$d['customer_name']??$d['name']??'',
                             'sr_number'=>$d['sr_number']??$d['ticket_id']??$rec->external_id??'',
                             'product_name'=>$d['product_name']??$d['product']??'',
                             'district'=>$d['district']??$d['area']??'','call_status'=>'pending']
                        );
                        $imported++;
                    }
                    $campaign->syncCounts();
                    Notification::make()->title("✅ {$imported} records import হয়েছে")->success()->send();
                }),
        ];
    }

    public function getSubheading(): ?string
    {
        $total    = FeedbackCampaign::count();
        $active   = FeedbackCampaign::where('status','active')->count();
        $surveyed = FeedbackSurvey::where('call_status','completed')->count();
        return "Total: {$total} | Active: {$active} | Surveys Completed: {$surveyed}";
    }
}
PHP;

// ── CreateFeedbackCampaign.php ────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackCampaigns/Pages/CreateFeedbackCampaign.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackCampaigns\Pages;
use App\Filament\Resources\FeedbackCampaigns\FeedbackCampaignResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFeedbackCampaign extends CreateRecord {
    protected static string $resource = FeedbackCampaignResource::class;
}
PHP;

// ── EditFeedbackCampaign.php ──────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackCampaigns/Pages/EditFeedbackCampaign.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackCampaigns\Pages;
use App\Filament\Resources\FeedbackCampaigns\FeedbackCampaignResource;
use Filament\Resources\Pages\EditRecord;
class EditFeedbackCampaign extends EditRecord {
    protected static string $resource = FeedbackCampaignResource::class;
}
PHP;

// ── FeedbackSurveyResource.php ────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackSurveys/FeedbackSurveyResource.php"] = <<<'PHP'
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
                    ->description(fn($r)=>"📱 {$r->mobile_number}"),
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
                TextColumn::make('called_at')->label('কলের সময়')->since()->sortable()->default('—'),
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
PHP;

// ── ListFeedbackSurveys.php ───────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackSurveys/Pages/ListFeedbackSurveys.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackSurveys\Pages;
use App\Filament\Resources\FeedbackSurveys\FeedbackSurveyResource;
use App\Models\FeedbackSurvey;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFeedbackSurveys extends ListRecords
{
    protected static string $resource = FeedbackSurveyResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('+ নতুন Entry')]; }
    public function getSubheading(): ?string
    {
        $total=$t=FeedbackSurvey::count();
        $completed=FeedbackSurvey::where('call_status','completed')->count();
        $satisfied=FeedbackSurvey::where('satisfied','yes')->count();
        $problems=FeedbackSurvey::where('has_problem','yes')->count();
        $pct=$completed>0?round(($satisfied/$completed)*100).'%':'—';
        return "Total: {$total} | Completed: {$completed} | Satisfied: {$satisfied} ({$pct}) | Problems: {$problems}";
    }
}
PHP;

// ── CreateFeedbackSurvey.php ──────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackSurveys/Pages/CreateFeedbackSurvey.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackSurveys\Pages;
use App\Filament\Resources\FeedbackSurveys\FeedbackSurveyResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFeedbackSurvey extends CreateRecord {
    protected static string $resource = FeedbackSurveyResource::class;
}
PHP;

// ── EditFeedbackSurvey.php ────────────────────────────────────────────────────
$files["$base/app/Filament/Resources/FeedbackSurveys/Pages/EditFeedbackSurvey.php"] = <<<'PHP'
<?php
namespace App\Filament\Resources\FeedbackSurveys\Pages;
use App\Filament\Resources\FeedbackSurveys\FeedbackSurveyResource;
use Filament\Resources\Pages\EditRecord;
class EditFeedbackSurvey extends EditRecord {
    protected static string $resource = FeedbackSurveyResource::class;
}
PHP;

// ── Write all files ───────────────────────────────────────────────────────────
echo "\n=== Writing Files ===\n";
foreach ($files as $path => $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($path, $content);
    echo "✓ ".str_replace($base,'',$path)."\n";
}

echo "\n✅ Done! Now run:\n";
echo "  php artisan migrate --force\n";
echo "  php artisan optimize:clear\n";
echo "\nAlso add to Asterisk extensions.conf:\n";
echo "[outbound-survey]\n";
echo "exten => s,1,Answer()\n";
echo " same => n,AGI(agi://YOUR_APP_IP/outbound-survey)\n";
echo " same => n,Hangup()\n";
