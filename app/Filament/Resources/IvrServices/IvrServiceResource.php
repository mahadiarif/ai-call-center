<?php

namespace App\Filament\Resources\IvrServices;

use App\Filament\Resources\IvrServices\Pages;
use App\Models\IvrService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class IvrServiceResource extends Resource
{
    protected static ?string $model = IvrService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationGroup = '⚙️ AI Configuration';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = "AI Settings (IVR)";
    protected static ?string $modelLabel = 'IVR Service';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 🚀 ম্যাজিক: প্রত্যেকটা ফিল্ডে ->columnSpan('full') দিয়ে জোর করে ১০০% লম্বা করে দিলাম!

                // ── Service Type — preset fields auto-fill ──────────────────────────
                Select::make('service_type')
                    ->label('📋 Service Type (টাইপ বেছে নিন — Fields auto-fill হবে)')
                    ->options([
                        'sr'            => '🔧 SR — Service Request (হোম সার্ভিস / রিপেয়ার)',
                        'qm_complaint'  => '⚠️ QM — অভিযোগ (Complaint)',
                        'qm_parts'      => '🔩 QM — Parts Query (যন্ত্রাংশ)',
                        'qm_bill'       => '💳 QM — Bill Query (বিল সংক্রান্ত)',
                        'survey'        => '📊 Outbound Survey (Feedback Call)',
                        'general'       => '💬 General (সাধারণ কল)',
                    ])
                    ->helperText('Type select করলে নিচে সেই type এর প্রয়োজনীয় fields দেখাবে — Repeater এ manually add করুন')
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        // Type অনুযায়ী system_prompt + required_fields preset করো
                        $presets = static::getPresetFields($state);
                        if (!empty($presets['fields'])) {
                            $set('required_fields', $presets['fields']);
                        }
                        if (!empty($presets['prompt'])) {
                            $set('system_prompt', $presets['prompt']);
                        }
                        if (!empty($presets['greeting'])) {
                            $set('greeting_message', $presets['greeting']);
                        }
                    })
                    ->columnSpan('full'),

                // Type অনুযায়ী hint দেখাবে
                \Filament\Forms\Components\Placeholder::make('type_hint')
                    ->label('')
                    ->content(fn ($get): string => match($get('service_type')) {
                        'sr'           => '✅ SR: customer_name, mobile_number, product_name, problem_description, district, address, barcode',
                        'qm_complaint' => '✅ QM Complaint: customer_name, mobile_number, complaint_category, person_name, showroom_address, incident_date, complaint_details',
                        'qm_parts'     => '✅ QM Parts: customer_name, mobile_number, product_name, model_number, parts_name, district',
                        'qm_bill'      => '✅ QM Bill: customer_name, mobile_number, sr_reference, bill_query_details, district',
                        'survey'       => '✅ Survey: কোনো field লাগবে না — AI automatically script follow করবে',
                        default        => '👆 উপরে type select করুন — তারপর নিচে সেই type এর fields auto-fill হবে',
                    })
                    ->columnSpan('full'),
                // ─────────────────────────────────────────────────────────────────

                TextInput::make('key_press')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('বাটন প্রেস (যেমন: 1)')
                    ->columnSpan('full'), // পুরো জায়গা নেবে
                    
                TextInput::make('service_name')
                    ->required()
                    ->label('সার্ভিসের নাম (যেমন: এসি কমপ্লেইন)')
                    ->columnSpan('full'),

                TextInput::make('ai_name')
                    ->label('এআই এজেন্টের নাম (যেমন: শর্মিলা)')
                    ->columnSpan('full'),
                    
                Toggle::make('is_active')
                    ->default(true)
                    ->label('চালু রাখুন? (Active)')
                    ->columnSpan('full'),

                Textarea::make('greeting_message')
                    ->rows(2)
                    ->label('ওয়েলকাম গ্রিটিংস (কল রিসিভ করেই কী বলবে)')
                    ->columnSpan('full'),

                Textarea::make('system_prompt')
                    ->required()
                    ->rows(4)
                    ->label('এআই পারসোনা এবং মূল নির্দেশনা')
                    ->columnSpan('full'),

                // 🚀 ডায়নামিক ফিল্ডস
                Repeater::make('required_fields')
                    ->label('কাস্টমার থেকে যে তথ্যগুলো নিতে হবে')
                    ->columnSpan('full')
                    ->columns(2)
                    ->schema([

                        // Row 1: field_name + ai_instruction side by side
                        TextInput::make('field_name')
                            ->required()
                            ->label('Field নাম (JSON key)')
                            ->helperText('AI এই নামে save করবে')
                            ->datalist([
                                'customer_name', 'mobile_number', 'alt_mobile_number',
                                'address', 'district', 'thana',
                                'product_name', 'model_number', 'barcode', 'serial_number',
                                'problem_description', 'purchase_date', 'warranty_status',
                                'complaint_category', 'complaint_details',
                                'person_name', 'showroom_address', 'incident_date',
                                'parts_name', 'sr_reference', 'bill_query_details', 'bill_amount',
                            ]),

                        TextInput::make('ai_instruction')
                            ->label('AI কীভাবে জিজ্ঞেস করবে')
                            ->placeholder('যেমন: কাস্টমারের পুরো নাম জানতে চাও'),

                        // Row 2: toggles + format hint
                        Toggle::make('is_mandatory')
                            ->default(true)
                            ->label('জরুরি তথ্য'),

                        Toggle::make('is_blocking')
                            ->default(false)
                            ->label('বাধ্যতামূলক (না দিলে এগোবে না)'),

                        // Optional: min/max length — compact, 1 row
                        TextInput::make('min_length')
                            ->label('Min দৈর্ঘ্য')
                            ->numeric()
                            ->placeholder('যেমন: 11')
                            ->helperText('মোবাইল=11, barcode=8'),

                        TextInput::make('max_length')
                            ->label('Max দৈর্ঘ্য')
                            ->numeric()
                            ->placeholder('যেমন: 11'),

                        TextInput::make('expected_format')
                            ->label('ফরম্যাট (AI validation)')
                            ->placeholder('যেমন: 11 সংখ্যা, 01 দিয়ে শুরু')
                            ->columnSpan(2),

                    ])
                    ->itemLabel(fn (array $state): ?string => $state['field_name'] ?? 'নতুন ফিল্ড')
                    ->collapsible()
                    ->collapsed()
                    ->defaultItems(1)
                    ->addActionLabel('➕ নতুন ফিল্ড যোগ করুন')
                    ->reorderable(true),

                // 🎙️ এআই ভয়েস সেটিংস
                Select::make('voice_gender')
                    ->label('AI কণ্ঠস্বর (Voice Model)')
                    ->options([
                        'Charon' => '🎙️ Charon - পুরুষ (গভীর, পরিষ্কার)',
                        'Aoede' => '🎤 Aoede - নারী (স্পষ্ট, friendly)',
                    ])
                    ->default('Charon')
                    ->helperText('শুধুমাত্র Charon এবং Aoede voice available। Database এ save হয়, mic-test page এ automatic use হবে।')
                    ->columnSpan('full'),

                TextInput::make('voice_speed')
                    ->label('কণ্ঠস্বর গতি (Speed)')
                    ->numeric()
                    ->step(0.1)
                    ->default(1.0)
                    ->minValue(0.25)
                    ->maxValue(2.0)
                    ->helperText('0.25 (খুব ধীর) থেকে 2.0 (খুব দ্রুত) - 1.0 সাধারণ গতি')
                    ->columnSpan('full'),

                // 🚨 Escalation Settings — রাগী/বিরক্ত কাস্টমার হ্যান্ডেলিং
                Section::make('🚨 Escalation Settings — রাগী কাস্টমার হ্যান্ডেলিং')
                    ->description('কাস্টমার রাগী বা বিরক্ত হলে AI কীভাবে সামলাবে এবং Human Agent এ পাঠাবে')
                    ->collapsed()
                    ->schema([

                        TextInput::make('escalation_agent_number')
                            ->label('Human Agent এর ফোন নম্বর')
                            ->placeholder('যেমন: 01712345678')
                            ->helperText('এই নম্বরে কাস্টমারকে refer করা হবে')
                            ->columnSpan('full'),

                        Select::make('escalation_trigger')
                            ->label('কখন Escalate করবে?')
                            ->options([
                                'angry'      => '😡 কাস্টমার রাগী/গালিগালাজ করলে',
                                'repeated'   => '🔁 একই কথা ৩বারের বেশি বললে',
                                'requested'  => '🙋 কাস্টমার নিজে Agent চাইলে',
                                'frustrated' => '😤 কাস্টমার বিরক্ত/হতাশ হলে',
                                'any'        => '⚡ উপরের যেকোনো কারণে',
                            ])
                            ->default('any')
                            ->columnSpan('full'),

                        Textarea::make('escalation_calm_script')
                            ->label('Escalate করার আগে AI কী বলবে? (শান্ত করার script)')
                            ->placeholder('যেমন: "আমি সত্যিই দুঃখিত স্যার। আপনার সমস্যাটা সঠিকভাবে সমাধান করতে আমাদের একজন বিশেষজ্ঞ আপনার সাথে কথা বলবেন।"')
                            ->helperText('এই কথা বলে কাস্টমারকে শান্ত করবে এবং agent এ পাঠাবে')
                            ->rows(3)
                            ->columnSpan('full'),

                        Textarea::make('escalation_hold_script')
                            ->label('Agent connect হওয়ার আগে কাস্টমারকে hold এ রাখার script')
                            ->placeholder('যেমন: "একটু ধৈর্য ধরুন স্যার, আমি এখনই আপনাকে আমাদের সিনিয়র এজেন্টের সাথে কানেক্ট করছি। লাইনে থাকুন।"')
                            ->rows(2)
                            ->columnSpan('full'),

                        Textarea::make('escalation_instructions')
                            ->label('এই IVR এর জন্য বিশেষ Escalation নির্দেশনা (AI prompt এ যাবে)')
                            ->placeholder('যেমন: AC complaint এর ক্ষেত্রে রাগী কাস্টমার হলে প্রথমে warranty check করো, তারপর escalate করো')
                            ->rows(3)
                            ->columnSpan('full'),

                        Toggle::make('escalation_collect_before_transfer')
                            ->label('Transfer এর আগে কাস্টমারের নাম ও নম্বর collect করবে?')
                            ->default(true)
                            ->helperText('Agent কে সাহায্য করতে আগে থেকে তথ্য নেওয়া হবে')
                            ->columnSpan('full'),
                    ]),

                // 🎯 Secondary Option — কাস্টমার সরাসরি Agent চাইলে
                Section::make('🎯 Secondary Option — সরাসরি Agent Forward')
                    ->description('কাস্টমার যদি AI এর সাথে কথা না বলে সরাসরি agent চায় — তাহলে AI আগে convince করবে, তারপর forward করবে')
                    ->collapsed()
                    ->schema([

                        Toggle::make('secondary_option_enabled')
                            ->label('Secondary Option চালু করবেন?')
                            ->default(false)
                            ->helperText('চালু করলে কাস্টমার "agent চাই" বললে নিচের নিয়ম কাজ করবে')
                            ->columnSpan('full'),

                        TextInput::make('secondary_agent_number')
                            ->label('Secondary Agent নম্বর (এই option এর জন্য আলাদা)')
                            ->placeholder('যেমন: 01812345678')
                            ->helperText('খালি রাখলে Escalation এর agent নম্বর ব্যবহার হবে')
                            ->columnSpan('full'),

                        Select::make('secondary_convince_attempts')
                            ->label('Forward এর আগে কতবার convince করবে?')
                            ->options([
                                '1' => '১ বার চেষ্টা করবে',
                                '2' => '২ বার চেষ্টা করবে (Recommended)',
                                '3' => '৩ বার চেষ্টা করবে',
                                '0' => 'সাথে সাথে forward করবে (convince করবে না)',
                            ])
                            ->default('2')
                            ->columnSpan('full'),

                        Repeater::make('secondary_convince_scripts')
                            ->label('Convince করার script — কাস্টমার agent চাইলে এই কথাগুলো বলবে')
                            ->columnSpan('full')
                            ->schema([
                                Textarea::make('script')
                                    ->required()
                                    ->label('Script')
                                    ->placeholder('যেমন: "স্যার, আমি আপনাকে এখনই সাহায্য করতে পারব। Agent এর চেয়ে দ্রুত সমাধান পাবেন। আপনার সমস্যাটা কী বলুন?"')
                                    ->rows(2)
                                    ->columnSpan('full'),
                            ])
                            ->addActionLabel('➕ আরও convince script যোগ করুন')
                            ->defaultItems(2)
                            ->collapsible(),

                        Textarea::make('secondary_forward_script')
                            ->label('Forward করার সময় AI কী বলবে?')
                            ->placeholder('যেমন: "ঠিক আছে স্যার, আমি আপনাকে এখনই আমাদের agent এর সাথে কানেক্ট করছি। লাইনে থাকুন।"')
                            ->rows(2)
                            ->columnSpan('full'),

                        Toggle::make('secondary_collect_name_mobile')
                            ->label('Forward এর আগে নাম ও মোবাইল নম্বর নেবে?')
                            ->default(true)
                            ->helperText('Agent কল রিসিভ করার আগেই কাস্টমারের তথ্য থাকবে')
                            ->columnSpan('full'),

                        Textarea::make('secondary_ai_instructions')
                            ->label('এই option এর জন্য AI কে বিশেষ নির্দেশনা')
                            ->placeholder('যেমন: কাস্টমার warranty নিয়ে জিজ্ঞেস করলে সরাসরি forward করো, convince করার দরকার নেই')
                            ->rows(2)
                            ->columnSpan('full'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key_press')->label('Key'),
                TextColumn::make('service_name')->label('Service'),
                TextColumn::make('serial_order')->label('Serial')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    // ─── Preset fields + prompt per service type ─────────────────────────────
    public static function getPresetFields(string $type): array
    {
        $presets = [

            'sr' => [
                'greeting' => '{time_greeting}! ওয়ালটন হেল্পলাইনে স্বাগতম। আমি আপনার সার্ভিস রিকোয়েস্ট নিতে সাহায্য করব।',
                'prompt'   => 'তুমি ওয়ালটন হেল্পলাইনের একজন বাস্তব কল সেন্টার এজেন্ট। কাস্টমারের হোম সার্ভিস রিকোয়েস্ট নিতে হবে। SR ticket তৈরি করো।',
                'fields'   => [
                    ['field_name'=>'customer_name',       'ai_instruction'=>'কাস্টমারের পুরো নাম জানতে চাও',                      'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'mobile_number',       'ai_instruction'=>'কাস্টমারের মোবাইল নম্বর জানতে চাও (১১ ডিজিট)',       'is_mandatory'=>true,  'is_blocking'=>true,  'min_length'=>11,'max_length'=>11,'expected_format'=>'11 সংখ্যা, 01 দিয়ে শুরু'],
                    ['field_name'=>'alt_mobile_number',   'ai_instruction'=>'বিকল্প মোবাইল নম্বর আছে কিনা জানতে চাও',            'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'product_name',        'ai_instruction'=>'পণ্যের নাম জানতে চাও (AC/TV/REF/Mobile ইত্যাদি)',    'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'model_number',        'ai_instruction'=>'পণ্যের মডেল নম্বর জানতে চাও',                       'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'barcode',             'ai_instruction'=>'পণ্যের বারকোড/সিরিয়াল নম্বর জানতে চাও',            'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'problem_description', 'ai_instruction'=>'পণ্যের সমস্যা বিস্তারিত জানতে চাও',                 'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'address',             'ai_instruction'=>'বাড়ির পূর্ণ ঠিকানা জানতে চাও',                     'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'district',            'ai_instruction'=>'জেলার নাম জানতে চাও',                               'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'purchase_date',       'ai_instruction'=>'পণ্য কবে কিনেছেন জানতে চাও (মাস/বছর হলেই চলবে)',   'is_mandatory'=>false, 'is_blocking'=>false],
                ],
            ],

            'qm_complaint' => [
                'greeting' => '{time_greeting}! ওয়ালটন হেল্পলাইনে স্বাগতম। আপনার অভিযোগ নথিভুক্ত করতে সাহায্য করব।',
                'prompt'   => 'তুমি ওয়ালটন হেল্পলাইনের একজন বাস্তব কল সেন্টার এজেন্ট। কাস্টমারের অভিযোগ (QM Complaint) নিতে হবে। বিস্তারিত শুনে নোট করো।',
                'fields'   => [
                    ['field_name'=>'customer_name',       'ai_instruction'=>'অভিযোগকারীর নাম জানতে চাও',                        'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'mobile_number',       'ai_instruction'=>'যোগাযোগের মোবাইল নম্বর জানতে চাও',               'is_mandatory'=>true,  'is_blocking'=>true,  'min_length'=>11,'max_length'=>11],
                    ['field_name'=>'complaint_category',  'ai_instruction'=>'অভিযোগের ধরন জানতে চাও (সার্ভিস এক্সপার্ট/শো-রুম/পণ্যের মান/বিল/অন্যান্য)', 'is_mandatory'=>true, 'is_blocking'=>true],
                    ['field_name'=>'person_name',         'ai_instruction'=>'কার বিরুদ্ধে অভিযোগ তার নাম/পরিচয় জানতে চাও',   'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'showroom_address',    'ai_instruction'=>'শো-রুম বা এলাকার নাম/ঠিকানা জানতে চাও',          'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'incident_date',       'ai_instruction'=>'ঘটনা কবে ঘটেছে জানতে চাও',                      'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'complaint_details',   'ai_instruction'=>'সম্পূর্ণ অভিযোগের বিবরণ বিস্তারিত শুনে নোট করো', 'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'district',            'ai_instruction'=>'জেলার নাম জানতে চাও',                            'is_mandatory'=>false, 'is_blocking'=>false],
                ],
            ],

            'qm_parts' => [
                'greeting' => '{time_greeting}! ওয়ালটন হেল্পলাইনে স্বাগতম। আপনার যন্ত্রাংশের অনুরোধ নিতে সাহায্য করব।',
                'prompt'   => 'তুমি ওয়ালটন হেল্পলাইনের একজন বাস্তব কল সেন্টার এজেন্ট। কাস্টমারের QM Parts Query নিতে হবে। কোন পার্টস লাগবে বিস্তারিত জানো।',
                'fields'   => [
                    ['field_name'=>'customer_name',    'ai_instruction'=>'কাস্টমারের নাম জানতে চাও',                         'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'mobile_number',    'ai_instruction'=>'মোবাইল নম্বর জানতে চাও',                          'is_mandatory'=>true,  'is_blocking'=>true, 'min_length'=>11,'max_length'=>11],
                    ['field_name'=>'product_name',     'ai_instruction'=>'কোন পণ্যের যন্ত্রাংশ লাগবে জানতে চাও',            'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'model_number',     'ai_instruction'=>'পণ্যের মডেল নম্বর জানতে চাও',                    'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'parts_name',       'ai_instruction'=>'কোন যন্ত্রাংশ/পার্টস লাগবে বিস্তারিত জানতে চাও', 'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'sr_reference',     'ai_instruction'=>'পূর্ববর্তী SR নম্বর আছে কিনা জানতে চাও',         'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'district',         'ai_instruction'=>'জেলার নাম জানতে চাও',                            'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'address',          'ai_instruction'=>'ডেলিভারি ঠিকানা জানতে চাও',                     'is_mandatory'=>false, 'is_blocking'=>false],
                ],
            ],

            'qm_bill' => [
                'greeting' => '{time_greeting}! ওয়ালটন হেল্পলাইনে স্বাগতম। আপনার বিল সংক্রান্ত প্রশ্নে সাহায্য করব।',
                'prompt'   => 'তুমি ওয়ালটন হেল্পলাইনের একজন বাস্তব কল সেন্টার এজেন্ট। কাস্টমারের QM Bill Query নিতে হবে। বিল নিয়ে কোনো সমস্যা বা প্রশ্ন বিস্তারিত শুনো।',
                'fields'   => [
                    ['field_name'=>'customer_name',      'ai_instruction'=>'কাস্টমারের নাম জানতে চাও',                             'is_mandatory'=>true,  'is_blocking'=>false],
                    ['field_name'=>'mobile_number',      'ai_instruction'=>'মোবাইল নম্বর জানতে চাও',                              'is_mandatory'=>true,  'is_blocking'=>true, 'min_length'=>11,'max_length'=>11],
                    ['field_name'=>'sr_reference',       'ai_instruction'=>'SR নম্বর বা রেফারেন্স নম্বর আছে কিনা জানতে চাও',     'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'bill_query_details', 'ai_instruction'=>'বিল সংক্রান্ত সমস্যা বা প্রশ্ন বিস্তারিত জানতে চাও', 'is_mandatory'=>true,  'is_blocking'=>true],
                    ['field_name'=>'bill_amount',        'ai_instruction'=>'বিলের পরিমাণ কত ছিল জানতে চাও (জানলে)',             'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'district',           'ai_instruction'=>'জেলার নাম জানতে চাও',                               'is_mandatory'=>false, 'is_blocking'=>false],
                ],
            ],

            'survey' => [
                'greeting' => '', // Outbound survey — script AIFormController থেকে আসবে
                'prompt'   => 'এটি একটি Outbound Feedback Survey call। AI getLiveSetup() থেকে automatic script পাবে। IVR তে আলাদা field লাগবে না।',
                'fields'   => [], // No fields — AI handles everything
            ],

            'general' => [
                'greeting' => '{time_greeting}! হেল্পলাইনে স্বাগতম। কীভাবে সাহায্য করতে পারি?',
                'prompt'   => 'তুমি একজন বাস্তব প্রফেশনাল কাস্টমার সাপোর্ট এজেন্ট। কাস্টমারের যেকোনো প্রশ্নে সাহায্য করো।',
                'fields'   => [
                    ['field_name'=>'customer_name', 'ai_instruction'=>'কাস্টমারের নাম জানতে চাও', 'is_mandatory'=>false, 'is_blocking'=>false],
                    ['field_name'=>'mobile_number', 'ai_instruction'=>'মোবাইল নম্বর জানতে চাও',  'is_mandatory'=>false, 'is_blocking'=>false],
                ],
            ],
        ];

        return $presets[$type] ?? [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIvrServices::route('/'),
            'create' => Pages\CreateIvrService::route('/create'),
            'edit' => Pages\EditIvrService::route('/{record}/edit'),
        ];
    }
}