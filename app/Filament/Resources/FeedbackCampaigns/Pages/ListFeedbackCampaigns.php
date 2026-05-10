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
                        ->label('Excel বা CSV ফাইল')
                        ->acceptedFileTypes([
                            'text/csv','text/plain','application/csv','application/octet-stream',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')->directory('survey-imports')
                        ->storeFiles(true)
                        ->helperText('Excel (.xlsx) বা CSV (.csv) — Column order: mobile_number, customer_name, sr_number, product_name, district, service_date')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $campaign = FeedbackCampaign::find($data['campaign_id']);
                    if (!$campaign) return;

                    $csvFile = $data['csv_file'];
                    if (is_array($csvFile)) $csvFile = reset($csvFile);

                    $storage = \Illuminate\Support\Facades\Storage::disk('local');
                    if (!$storage->exists('survey-imports')) {
                        $storage->makeDirectory('survey-imports');
                    }

                    $resolved = null;
                    foreach ([$csvFile, 'survey-imports/'.basename($csvFile), 'livewire-tmp/'.basename($csvFile)] as $try) {
                        if ($storage->exists($try)) { $resolved = $try; break; }
                    }
                    if (!$resolved) {
                        Notification::make()->title('❌ ফাইল পাওয়া যায়নি')->body('Path: '.($csvFile ?? 'empty'))->danger()->send();
                        return;
                    }

                    $filePath = $storage->path($resolved);
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $rows = [];

                    // ── Excel (.xlsx) ──────────────────────────────────────────
                    if ($ext === 'xlsx' || $ext === 'xls') {
                        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                            Notification::make()->title('❌ phpspreadsheet install করা নেই')
                                ->body('Server এ: composer require phpoffice/phpspreadsheet')
                                ->danger()->send();
                            return;
                        }
                        try {
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                            $sheet = $spreadsheet->getActiveSheet();
                            foreach ($sheet->getRowIterator() as $row) {
                                $cells = [];
                                foreach ($row->getCellIterator() as $cell) {
                                    $cells[] = (string)$cell->getValue();
                                }
                                $rows[] = $cells;
                            }
                        } catch (\Throwable $e) {
                            Notification::make()->title('❌ Excel পড়তে সমস্যা: '.$e->getMessage())->danger()->send();
                            return;
                        }
                    } else {
                        // ── CSV ────────────────────────────────────────────────
                        $content = $storage->get($resolved);
                        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!empty($line)) $rows[] = str_getcsv($line);
                        }
                    }

                    $imported = 0;
                    $first = true;
                    foreach ($rows as $row) {
                        if ($first) {
                            $first = false;
                            // skip header row (if first cell is not a phone number)
                            if (!preg_match('/^01[3-9]/', trim($row[0] ?? ''))) continue;
                        }
                        $mobile = trim($row[0] ?? '');
                        if (empty($mobile) || !preg_match('/^01[3-9]\d{8}$/', $mobile)) continue;
                        FeedbackSurvey::firstOrCreate(
                            ['campaign_id' => $campaign->id, 'mobile_number' => $mobile],
                            [
                                'customer_name' => trim($row[1] ?? ''),
                                'sr_number'     => trim($row[2] ?? ''),
                                'product_name'  => trim($row[3] ?? ''),
                                'district'      => trim($row[4] ?? ''),
                                'service_date'  => !empty($row[5]) ? trim($row[5]) : null,
                                'call_status'   => 'pending',
                            ]
                        );
                        $imported++;
                    }
                    $storage->delete($resolved);
                    $campaign->syncCounts();
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