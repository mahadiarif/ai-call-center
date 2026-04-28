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
    protected static ?string $navigationLabel = 'IVR Services';
    protected static ?string $modelLabel = 'IVR Service';
    protected static string|\UnitEnum|null $navigationGroup = 'AI সেটিংস';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 🚀 ম্যাজিক: প্রত্যেকটা ফিল্ডে ->columnSpan('full') দিয়ে জোর করে ১০০% লম্বা করে দিলাম!
                
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
                    ->schema([

                        // ✅ Field name — suggestion সহ
                        TextInput::make('field_name')
                            ->required()
                            ->label('ফিল্ডের নাম (AI এই নামে JSON এ save করবে)')
                            ->helperText('পরিচিত নাম দিলে AI ভালো বুঝবে। নিচে suggestion আছে।')
                            ->datalist([
                                'customer_name', 'mobile_number', 'alt_mobile_number',
                                'address', 'district', 'thana', 'post_code',
                                'product_name', 'product_model', 'barcode', 'serial_number',
                                'problem_description', 'purchase_date', 'warranty_status',
                                'service_type', 'complaint_type', 'email',
                                'nid_number', 'account_number', 'reference_number',
                            ])
                            ->columnSpan('full'),

                        TextInput::make('ai_instruction')
                            ->label('AI কীভাবে জিজ্ঞেস করবে')
                            ->placeholder('যেমন: কাস্টমারের পুরা নাম জানতে চাও')
                            ->columnSpan('full'),

                        Toggle::make('is_mandatory')
                            ->default(true)
                            ->label('জরুরি তথ্য? (চেষ্টা করবে নেওয়ার)')
                            ->columnSpan('full'),

                        Toggle::make('is_blocking')
                            ->default(false)
                            ->label('কঠোর বাধ্যতামূলক? (না দিলে এগোবে না)')
                            ->columnSpan('full'),

                        // ✅ Sample values + length validation (সবচেয়ে গুরুত্বপূর্ণ)
                        Section::make('📏 ডাটা ফরম্যাট ও Sample (AI validation এর জন্য)')
                            ->collapsed()
                            ->schema([
                                Textarea::make('sample_values')
                                    ->label('Sample মান (একটি লাইনে একটি করে)')
                                    ->placeholder("01712345678\n01987654321\n017-XXXXXXXX")
                                    ->helperText('এই sample দেখে AI বুঝবে সঠিক ডাটা কেমন হওয়া উচিত')
                                    ->rows(3)
                                    ->columnSpan('full'),

                                TextInput::make('min_length')
                                    ->label('সর্বনিম্ন character/digit সংখ্যা')
                                    ->numeric()
                                    ->placeholder('যেমন: 11 (মোবাইল), 4 (barcode)')
                                    ->columnSpan('full'),

                                TextInput::make('max_length')
                                    ->label('সর্বোচ্চ character/digit সংখ্যা')
                                    ->numeric()
                                    ->placeholder('যেমন: 11 (মোবাইল), 20 (barcode)')
                                    ->columnSpan('full'),

                                TextInput::make('expected_format')
                                    ->label('ফরম্যাট বর্ণনা (AI কে বোঝানোর জন্য)')
                                    ->placeholder('যেমন: 11 সংখ্যা, 01 দিয়ে শুরু | অথবা: 6-16 digit')
                                    ->columnSpan('full'),

                                TextInput::make('error_message')
                                    ->label('ভুল ডাটা দিলে AI কী বলবে?')
                                    ->placeholder('যেমন: মোবাইল নম্বরটি ১১ ডিজিটের হতে হবে')
                                    ->columnSpan('full'),
                            ]),

                        // ✅ Advanced options — collapse করা
                        Section::make('⚙️ Advanced Options (ঐচ্ছিক)')
                            ->collapsed()
                            ->schema([
                                TextInput::make('convincing_logic')
                                    ->label('তথ্য না দিলে কীভাবে বোঝাবে?')
                                    ->columnSpan('full'),

                                TextInput::make('max_retries')
                                    ->label('সর্বোচ্চ কতবার চেষ্টা করবে?')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->columnSpan('full'),

                                Toggle::make('needs_confirmation')
                                    ->label('বললে AI রিপিট করে confirm করবে?')
                                    ->default(true)
                                    ->helperText('"০১৭১২... — এটা কি ঠিক আছে?"')
                                    ->columnSpan('full'),

                                TextInput::make('depends_on_field')
                                    ->label('কোন field পাওয়ার পরে জিজ্ঞেস করবে? (Conditional)')
                                    ->datalist([
                                        'customer_name', 'mobile_number', 'product_name',
                                        'barcode', 'address', 'district',
                                    ])
                                    ->columnSpan('full'),

                                TextInput::make('indirect_question')
                                    ->label('ঘুরিয়ে জিজ্ঞেস করার উপায় (Indirect)')
                                    ->placeholder('যেমন: "আপনি কি ঢাকায় থাকেন?"')
                                    ->columnSpan('full'),

                                Repeater::make('validation_formats')
                                    ->label('ভ্যালিডেশন রুল')
                                    ->columnSpan('full')
                                    ->schema([
                                        TextInput::make('rule')
                                            ->required()
                                            ->label('রুল')
                                            ->placeholder('যেমন: 11 digits, Starts with 01')
                                            ->columnSpan('full'),
                                    ])
                                    ->addActionLabel('➕ রুল যোগ করুন')
                                    ->defaultItems(0)
                                    ->collapsible(),
                            ]),

                        // ✅ FAQ
                        Section::make('❓ FAQ — কাস্টমারের পাল্টা প্রশ্ন ও AI এর উত্তর')
                            ->collapsed()
                            ->schema([
                                Repeater::make('custom_qna')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->schema([
                                        TextInput::make('customer_question')
                                            ->required()
                                            ->label('কাস্টমার যদি জিজ্ঞেস করে...')
                                            ->columnSpan('full'),
                                        Textarea::make('ai_answer')
                                            ->required()
                                            ->label('AI তখন এই উত্তর দেবে...')
                                            ->columnSpan('full'),
                                    ])
                                    ->addActionLabel('➕ প্রশ্নোত্তর যোগ করুন')
                                    ->defaultItems(0),
                            ]),

                    ])
                    ->itemLabel(fn (array $state): ?string => $state['field_name'] ?? 'নতুন ফিল্ড')
                    ->collapsible()
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
                TextColumn::make('key_press')->label('কী (Key)'),
                TextColumn::make('service_name')->label('সার্ভিস'),
                TextColumn::make('serial_order')->label('সিরিয়াল')->sortable(),
                IconColumn::make('is_active')->boolean()->label('অ্যাকটিভ'),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
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