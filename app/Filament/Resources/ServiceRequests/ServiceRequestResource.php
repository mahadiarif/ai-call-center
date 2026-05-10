<?php

namespace App\Filament\Resources\ServiceRequests;

use App\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Resources\ServiceRequests\Pages\EditServiceRequest;
use App\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\ServiceRequest;
use App\Models\IvrService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = "Service Requests";

    public static function form(Schema $schema): Schema
    {
        $isQm = fn ($get) => in_array($get('ticket_type'), ['QM_COMPLAINT', 'QM_PARTS', 'QM_BILL']);
        $isSr = fn ($get) => !in_array($get('ticket_type'), ['QM_COMPLAINT', 'QM_PARTS', 'QM_BILL']);

        $components = [
            // ── Ticket Type selector — সবার উপরে ──
            \Filament\Forms\Components\Select::make("ticket_type")
                ->label("🎫 Ticket Type")
                ->options([
                    'SR'           => '📋 SR — সার্ভিস রিকোয়েস্ট',
                    'QM_COMPLAINT' => '🎫 QM Complaint — অভিযোগ',
                    'QM_PARTS'     => '🔧 QM Parts — পার্টস Query',
                    'QM_BILL'      => '💰 QM Bill — বিল Query',
                ])
                ->default('SR')
                ->live()
                ->columnSpanFull(),

            // ── QM Number (শুধু QM হলে) ──
            \Filament\Forms\Components\TextInput::make("qm_number")
                ->label("QM নম্বর")
                ->disabled()
                ->helperText("Auto-generated")
                ->visible(fn ($get) => $isQm($get)),

            // ── Common fields (সব type-এ) ──
            \Filament\Forms\Components\TextInput::make("customer_name")->label("Customer Name"),
            \Filament\Forms\Components\TextInput::make("mobile_number")->label("Mobile Number")->required(),
            \Filament\Forms\Components\TextInput::make("alt_mobile_number")->label("Alternate Mobile Number"),
            \Filament\Forms\Components\TextInput::make("address")->label("Address"),
            \Filament\Forms\Components\TextInput::make("district")->label("District"),

            // ── SR-only fields ──
            \Filament\Forms\Components\TextInput::make("product_name")
                ->label("Product Name")
                ->visible(fn ($get) => $isSr($get)),
            \Filament\Forms\Components\TextInput::make("barcode")
                ->label("Barcode/Serial Number")
                ->visible(fn ($get) => $isSr($get)),
            \Filament\Forms\Components\Textarea::make("problem_description")
                ->label("Problem Description")->rows(3)
                ->visible(fn ($get) => $isSr($get)),
            \Filament\Forms\Components\TextInput::make("service_center")
                ->label("Service Center")
                ->visible(fn ($get) => $isSr($get)),
            \Filament\Forms\Components\TextInput::make("brand")
                ->label("Brand")->default("WALTON")
                ->visible(fn ($get) => $isSr($get)),

            // ── QM_PARTS — পার্টস fields ──
            \Filament\Forms\Components\TextInput::make("product_name")
                ->label("পণ্যের নাম")
                ->visible(fn ($get) => $get('ticket_type') === 'QM_PARTS'),

            // ── Comments (সব type-এ — QM-এ বিস্তারিত থাকে) ──
            \Filament\Forms\Components\Textarea::make("comments")
                ->label("Comments / QM বিস্তারিত")->rows(6),

            // ── QM বিস্তারিত panel (extracted_data থেকে) ──
            \Filament\Forms\Components\Placeholder::make('qm_detail_panel')
                ->label('')
                ->content(function ($record) {
                    if (!$record) return new \Illuminate\Support\HtmlString('');
                    $type = $record->ticket_type ?? 'SR';
                    if ($type === 'SR') return new \Illuminate\Support\HtmlString('');
                    $data = $record->extracted_data ?? [];

                    if ($type === 'QM_COMPLAINT') {
                        $html = '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 16px;margin-top:4px;">';
                        $html .= '<div style="font-weight:700;color:#dc2626;margin-bottom:10px;">🔴 অভিযোগের বিস্তারিত</div>';
                        if (!empty($data['complaint_category'])) {
                            $cat = match($data['complaint_category']) {
                                'service_expert'  => 'সার্ভিস এক্সপার্ট', 'showroom' => 'শো-রুম / প্লাজা',
                                'product_quality' => 'পণ্যের মান', 'billing' => 'বিল',
                                default => $data['complaint_category'],
                            };
                            $html .= '<div style="margin-bottom:6px;"><strong>অভিযোগের ধরন:</strong> '  . e($cat) . '</div>';
                        }
                        if (!empty($data['sr_number']))        $html .= '<div style="margin-bottom:6px;"><strong>SR রেফারেন্স:</strong> ' . e($data['sr_number']) . '</div>';
                        if (!empty($data['person_name']))      $html .= '<div style="margin-bottom:6px;"><strong>অভিযোগকৃত ব্যক্তি:</strong> ' . e($data['person_name']) . '</div>';
                        if (!empty($data['showroom_address'])) $html .= '<div style="margin-bottom:6px;"><strong>শো-রুম:</strong> ' . e($data['showroom_address']) . '</div>';
                        if (!empty($data['incident_date']))    $html .= '<div style="margin-bottom:6px;"><strong>ঘটনার তারিখ:</strong> ' . e($data['incident_date']) . '</div>';
                        if (!empty($data['complaint_details'])) {
                            $html .= '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #fecaca;">';
                            $html .= '<strong>🔴 সম্পূর্ণ অভিযোগ বিবরণ:</strong>';
                            $html .= '<div style="margin-top:6px;white-space:pre-wrap;line-height:1.7;background:#fff5f5;padding:10px;border-radius:6px;">' . e($data['complaint_details']) . '</div>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);

                    } elseif ($type === 'QM_PARTS') {
                        $html = '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;margin-top:4px;">';
                        $html .= '<div style="font-weight:700;color:#1d4ed8;margin-bottom:10px;">🔧 পার্টস Query বিস্তারিত</div>';
                        if (!empty($data['product_model']))           $html .= '<div style="margin-bottom:6px;"><strong>মডেল:</strong> ' . e($data['product_model']) . '</div>';
                        if (!empty($data['preferred_service_point'])) $html .= '<div style="margin-bottom:6px;"><strong>পছন্দের সার্ভিস পয়েন্ট:</strong> ' . e($data['preferred_service_point']) . '</div>';
                        if (!empty($data['parts_name'])) {
                            $html .= '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #bfdbfe;">';
                            $html .= '<strong>🔧 প্রয়োজনীয় পার্টসের বিবরণ:</strong>';
                            $html .= '<div style="margin-top:6px;white-space:pre-wrap;line-height:1.7;background:#f0f9ff;padding:10px;border-radius:6px;">' . e($data['parts_name']) . '</div>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);

                    } elseif ($type === 'QM_BILL') {
                        $html = '<div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;margin-top:4px;">';
                        $html .= '<div style="font-weight:700;color:#92400e;margin-bottom:10px;">💰 বিল Query বিস্তারিত</div>';
                        if (!empty($data['sr_number'])) $html .= '<div style="margin-bottom:6px;"><strong>SR রেফারেন্স:</strong> ' . e($data['sr_number']) . '</div>';
                        if (!empty($data['bill_query_details'])) {
                            $html .= '<div style="margin-top:8px;padding-top:8px;border-top:1px solid #fde68a;">';
                            $html .= '<strong>💰 বিল সংক্রান্ত প্রশ্ন / মন্তব্য:</strong>';
                            $html .= '<div style="margin-top:6px;white-space:pre-wrap;line-height:1.7;background:#fffbeb;padding:10px;border-radius:6px;">' . e($data['bill_query_details']) . '</div>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }
                    return new \Illuminate\Support\HtmlString('');
                })
                ->columnSpanFull()
                ->visible(fn ($get) => $isQm($get)),

            \Filament\Forms\Components\Select::make("status")
                ->label("Status")
                ->options([
                    "Incoming"  => "Incoming",
                    "Pending"   => "Pending",
                    "Escalation Requested" => "Escalation Requested",
                    "Resolved"  => "Resolved",
                    "Rejected"  => "Rejected",
                    "Drop Call" => "Drop Call",
                ])
                ->required(),
            \Filament\Forms\Components\Textarea::make("call_transcript")
                ->label("Call Transcript")
                ->rows(5),

            // 🧠 Previous Tickets History — same mobile-এর আগের সব ticket
            \Filament\Forms\Components\Placeholder::make('previous_tickets_history')
                ->label('🔁 Previous Tickets (Customer History)')
                ->content(function ($record) {
                    if (!$record || empty($record->mobile_number)) {
                        return new \Illuminate\Support\HtmlString(
                            '<div style="color:#999;font-style:italic;">কোনো mobile number নেই — history দেখানো সম্ভব না।</div>'
                        );
                    }

                    $previous = \App\Models\ServiceRequest::where('mobile_number', $record->mobile_number)
                        ->where('id', '!=', $record->id)
                        ->orderByDesc('created_at')
                        ->limit(20)
                        ->get();

                    if ($previous->isEmpty()) {
                        return new \Illuminate\Support\HtmlString(
                            '<div style="color:#10b981;padding:8px 12px;background:#ecfdf5;border-radius:6px;border-left:4px solid #10b981;">
                                ✨ এটা এই কাস্টমারের প্রথম call — আগের কোনো history নেই।
                            </div>'
                        );
                    }

                    $totalCalls = $previous->count() + 1;
                    $html = '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">';
                    $html .= '<div style="background:#1e40af;color:white;padding:8px 12px;font-weight:600;">';
                    $html .= "🔁 Returning Customer — মোট {$totalCalls}টি call ({$previous->count()}টি আগের ticket)";
                    $html .= '</div>';

                    $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
                    $html .= '<thead><tr style="background:#f1f5f9;">';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">#</th>';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">তারিখ</th>';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">পণ্য</th>';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">সমস্যা</th>';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">Status</th>';
                    $html .= '<th style="padding:6px 8px;text-align:left;border-bottom:1px solid #e2e8f0;">Action</th>';
                    $html .= '</tr></thead><tbody>';

                    foreach ($previous as $p) {
                        $statusColor = match($p->status) {
                            'Resolved' => '#10b981',
                            'Pending'  => '#f59e0b',
                            'Drop Call', 'Rejected' => '#ef4444',
                            'Escalation Requested' => '#8b5cf6',
                            default    => '#6b7280',
                        };
                        $html .= '<tr style="border-bottom:1px solid #f1f5f9;">';
                        $html .= '<td style="padding:6px 8px;font-weight:600;">#'.$p->id.'</td>';
                        $html .= '<td style="padding:6px 8px;">'.$p->created_at->format('d M Y, h:i A').'</td>';
                        $html .= '<td style="padding:6px 8px;">'.e($p->product_name ?: '-').'</td>';
                        $html .= '<td style="padding:6px 8px;max-width:300px;">'.e(\Illuminate\Support\Str::limit($p->problem_description, 80)).'</td>';
                        $html .= '<td style="padding:6px 8px;"><span style="background:'.$statusColor.';color:white;padding:2px 8px;border-radius:4px;font-size:11px;">'.e($p->status).'</span></td>';
                        $html .= '<td style="padding:6px 8px;"><a href="/admin/service-requests/'.$p->id.'/edit" target="_blank" style="color:#2563eb;text-decoration:underline;">View →</a></td>';
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table></div>';

                    return new \Illuminate\Support\HtmlString($html);
                })
                ->columnSpanFull()
                ->visibleOn('edit'),
        ];

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")->label("#")->sortable()->width("50px"),
                TextColumn::make("customer_name")->label("Name")->searchable()
                    ->default("N/A")
                    ->description(fn ($record) => $record->mobile_number ? "\ud83d\udcf1 {$record->mobile_number}" : null)
                    ->sortable(),
                // QM Type + QM Number column
                TextColumn::make("ticket_type")->label("Type")
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'QM_COMPLAINT' => 'danger',
                        'QM_PARTS'     => 'info',
                        'QM_BILL'      => 'warning',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'QM_COMPLAINT' => '🎫 QM Complaint',
                        'QM_PARTS'     => '🔧 QM Parts',
                        'QM_BILL'      => '💰 QM Bill',
                        default        => '📋 SR',
                    })
                    ->description(fn ($record) => $record->qm_number ? $record->qm_number : null)
                    ->sortable(),
                TextColumn::make("ivrService.service_name")->label("Category")->badge()->color("info")->default("N/A"),
                TextColumn::make("problem_description")->label("Problem / Details")->limit(60)
                    ->default("-")
                    ->description(fn ($record) => $record->product_name ? "\ud83d\udce6 {$record->product_name}" : null)
                    ->tooltip(fn ($record) => $record->comments ?: $record->problem_description),
                TextColumn::make("status")->label("Status")->badge()
                    ->color(fn (string $state): string => match($state) {
                        "Incoming"  => "info",
                        "Pending"   => "warning",
                        "Escalation Requested" => "purple",
                        "Drop Call" => "danger",
                        "Resolved"  => "success",
                        "Rejected"  => "danger",
                        default     => "gray",
                    })->sortable(),
                TextColumn::make("created_at")->label("Time")->since()->sortable()
                    ->description(fn ($record) => $record->call_transcript ? "\ud83c\udfac Transcript" : null)
                    ->tooltip(fn ($record) => $record->created_at?->format("d M Y, h:i A")),
            ])
            ->defaultSort("created_at", "desc")
            ->filters([
                SelectFilter::make("status")->label("Status")->options([
                    "Incoming"  => "Incoming",
                    "Pending"   => "Pending",
                    "Escalation Requested" => "Escalation Requested",
                    "Drop Call" => "Drop Call",
                    "Resolved"  => "Resolved",
                    "Rejected"  => "Rejected",
                ]),
                SelectFilter::make("ticket_type")->label("Ticket Type")->options([
                    'SR'           => '📋 SR — সার্ভিস রিকোয়েস্ট',
                    'QM_COMPLAINT' => '🎫 QM Complaint',
                    'QM_PARTS'     => '🔧 QM Parts',
                    'QM_BILL'      => '💰 QM Bill',
                ]),
                SelectFilter::make("ivr_service_id")->label("Category")
                    ->relationship("ivrService", "service_name"),
                Filter::make("created_at")->label("Date Range")
                    ->form([
                        DatePicker::make("from")->label("From"),
                        DatePicker::make("until")->label("Until"),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data["from"],  fn ($q) => $q->whereDate("created_at", ">=", $data["from"]))
                        ->when($data["until"], fn ($q) => $q->whereDate("created_at", "<=", $data["until"]))
                    ),
            ])
            ->actions([
                EditAction::make()->label("")->tooltip("Edit"),
                DeleteAction::make()->label("")->tooltip("Delete"),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn ($record) => static::getUrl("edit", ["record" => $record]));
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            "index"  => ListServiceRequests::route("/"),
            "create" => CreateServiceRequest::route("/create"),
            "edit"   => EditServiceRequest::route("/{record}/edit"),
        ];
    }
}
