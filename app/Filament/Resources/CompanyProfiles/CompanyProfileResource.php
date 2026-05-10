<?php

namespace App\Filament\Resources\CompanyProfiles;

use App\Filament\Resources\CompanyProfiles\Pages\CreateCompanyProfile;
use App\Filament\Resources\CompanyProfiles\Pages\EditCompanyProfile;
use App\Filament\Resources\CompanyProfiles\Pages\ListCompanyProfiles;
use App\Filament\Resources\CompanyProfiles\Schemas\CompanyProfileForm;
use App\Filament\Resources\CompanyProfiles\Tables\CompanyProfilesTable;
use App\Models\CompanyProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyProfileResource extends Resource
{
    protected static ?string $model = CompanyProfile::class;
    protected static string | \UnitEnum | null $navigationGroup = '🛡️ System Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = "Company Profile";

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Tabs::make('AI Control Panel')
                    ->tabs([
                        // 🚀 ট্যাব ১: মাল্টিপল কোম্পানি প্রোফাইল ও লোগো
                        \Filament\Schemas\Components\Tabs\Tab::make('১. কোম্পানি প্রোফাইল')->schema([
                            \Filament\Forms\Components\FileUpload::make('company_logo')
                                ->image()
                                ->disk('public')
                                ->directory('company-logos')
                                ->visibility('public')
                                ->imagePreviewHeight('80')
                                ->label('কোম্পানির লোগো (সাইডবারে দেখাবে)')
                                ->helperText('আপলোড করলে সাইডবারে লোগো দেখাবে')
                                ->columnSpanFull(),

                            \Filament\Forms\Components\TextInput::make('company_name')
                                ->required()
                                ->label('কোম্পানির নাম (যেমন: Walton BD)'),
                                
                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->label('এই প্রোফাইল চালু রাখবেন?'),

                            \Filament\Forms\Components\Textarea::make('about_company')
                                ->rows(3)
                                ->label('কোম্পানির বেসিক সার্ভিস ও ঠিকানা')->columnSpanFull(),

                            // 🚀 আনলিমিটেড ফিল্ড যোগ করার ম্যাজিক (Key-Value)
                            \Filament\Forms\Components\KeyValue::make('contact_info')
                                ->label('অন্যান্য তথ্য (ইচ্ছামতো যোগ করুন)')
                                ->keyLabel('তথ্যের নাম (যেমন: Email, Phone, Website)')
                                ->valueLabel('বিস্তারিত (যেমন: info@walton.bd)')
                                ->addActionLabel('➕ নতুন তথ্য যোগ করুন')
                                ->columnSpanFull(),
                        ])->columns(2),

                        // ট্যাব ২: AI Persona & Training
                        \Filament\Schemas\Components\Tabs\Tab::make('২. AI Persona & Training')->schema([
                            \Filament\Forms\Components\Textarea::make('global_persona')
                                ->label('AI এর মূল চরিত্র ও ব্যবহার (Global Persona)')
                                ->placeholder('যেমন: তুমি Walton এর একজন বিনয়ী, সহায়ক কাস্টমার সাপোর্ট এজেন্ট। তুমি বাংলায় কথা বলো এবং সর্বদা সম্মানজনক ভাষা ব্যবহার করো।')
                                ->helperText('এটাই AI এর "ব্যক্তিত্ব" — সব IVR এ এই persona কাজ করবে')
                                ->rows(4)
                                ->columnSpanFull(),

                            \Filament\Forms\Components\Textarea::make('ai_forbidden_topics')
                                ->label('AI যেসব বিষয়ে কথা বলবে না (Forbidden Topics)')
                                ->placeholder("রাজনীতি\nধর্ম\nপ্রতিযোগী কোম্পানির সমালোচনা\nব্যক্তিগত মতামত")
                                ->helperText('প্রতিটা line এ একটা করে বিষয় লিখুন')
                                ->rows(4)
                                ->columnSpanFull(),

                            \Filament\Forms\Components\Textarea::make('ai_tone_guidelines')
                                ->label('AI এর কথা বলার ধরন (Tone Guidelines)')
                                ->placeholder("• সর্বদা বিনয়ী ও ধৈর্যশীল থাকবে\n• কাস্টমারকে 'স্যার/ম্যাডাম' বলে সম্বোধন করবে\n• জটিল প্রশ্নে সহজ ভাষায় উত্তর দেবে\n• কখনো রাগ দেখাবে না")
                                ->rows(5)
                                ->columnSpanFull(),

                            \Filament\Forms\Components\Textarea::make('ai_special_knowledge')
                                ->label('AI এর বিশেষ জ্ঞান ও তথ্য (Special Training)')
                                ->placeholder("যেমন:\n• Walton এর warranty policy: ১ বছর সার্ভিস ওয়ারেন্টি\n• Service center ঢাকায় ৩টি, চট্টগ্রামে ২টি\n• Emergency contact: 16267")
                                ->helperText('এই তথ্য AI সব কলে মনে রাখবে এবং প্রয়োজনে বলবে')
                                ->rows(6)
                                ->columnSpanFull(),
                        ]),

                        // ট্যাব ৩: গ্রিটিংস কন্ট্রোল
                        \Filament\Schemas\Components\Tabs\Tab::make('৩. গ্রিটিংস কন্ট্রোল')->schema([
                            \Filament\Forms\Components\Radio::make('greeting_behavior')
                                ->label('এআই কখন কথা বলা শুরু করবে?')
                                ->options([
                                    'ai_first' => 'এআই কল রিসিভ করেই নিজে থেকে আগে গ্রিটিংস দেবে',
                                    'user_first' => 'কাস্টমার "হ্যালো" বললে তারপর এআই কথা শুরু করবে',
                                ])
                                ->default('ai_first')->inline(),
                        ]),

                        // ট্যাব ৪: ডাইনামিক ইনস্ট্রাকশনস
                        \Filament\Schemas\Components\Tabs\Tab::make('৪. ডাইনামিক ইনস্ট্রাকশনস')->schema([
                            \Filament\Forms\Components\Repeater::make('dynamic_instructions')
                                ->label('এআই এর জন্য কাস্টম নিয়মাবলি যোগ করুন')
                                ->schema([
                                    \Filament\Forms\Components\TextInput::make('rule_title')
                                        ->required()->label('নিয়মের নাম'),

                                    // 🚀 কোন IVR এর জন্য এই নিয়ম সেটা সিলেক্ট করার অপশন!
                                    \Filament\Forms\Components\Select::make('ivr_service_id')
                                        ->label('এই নিয়মটি কোন সার্ভিসের জন্য?')
                                        ->options(\App\Models\IvrService::pluck('service_name', 'id'))
                                        ->placeholder('সব সার্ভিসের জন্য (Global)'),
                                        
                                    \Filament\Forms\Components\Textarea::make('rule_details')
                                        ->required()->rows(2)->label('বিস্তারিত নিয়ম'),
                                        
                                    \Filament\Forms\Components\Toggle::make('is_active')
                                        ->default(true)->label('চালু রাখবেন?'),
                                ])
                                ->columns(2)
                                ->addActionLabel('➕ নতুন নিয়ম যোগ করুন')
                                ->reorderable(true)->collapsible(),
                        ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return CompanyProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyProfiles::route('/'),
            'create' => CreateCompanyProfile::route('/create'),
            'edit' => EditCompanyProfile::route('/{record}/edit'),
        ];
    }
}
