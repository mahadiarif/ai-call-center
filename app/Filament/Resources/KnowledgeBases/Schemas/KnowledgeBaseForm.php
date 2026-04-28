<?php

namespace App\Filament\Resources\KnowledgeBases\Schemas;

use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput; 
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\IvrService;

class KnowledgeBaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                // --- ক্যাটাগরি এবং ব্র্যান্ড ---
                Select::make('category')
                    ->label('ক্যাটাগরি (Category)')
                    ->options([
                        'Instruction' => '🧠 এআই ব্রেইন (Persona/Rules)',
                        'TV' => '📺 টিভি (TV)',
                        'AC' => '❄️ এসি (AC)',
                        'Fridge' => '🥶 ফ্রিজ (Fridge)',
                    ])
                    ->searchable()
                    ->required(),
                    
                Select::make('brand_name')
                    ->label('ব্র্যান্ডের নাম (Brand)')
                    ->options([
                        'Walton' => 'Walton',
                        'Marcel' => 'Marcel',
                        'MyOne' => 'MyOne',
                    ])
                    ->searchable(),

                // 🆕 কোন IVR সার্ভিসের জন্য (null = সব সার্ভিসে কাজ করবে)
                Select::make('ivr_service_id')
                    ->label('🔧 কোন IVR সার্ভিসের জন্য?')
                    ->options(IvrService::where('is_active', true)->pluck('service_name', 'id'))
                    ->placeholder('সব সার্ভিসের জন্য (Global)')
                    ->searchable()
                    ->nullable(),

                // --- এআই ব্রেইন ট্রেইনিং ---
                TagsInput::make('greeting_rules')
                    ->label('শুরুর কথা (Greeting)')
                    ->suggestions([
                        'আসসালামু আলাইকুম, ওয়ালটন থেকে বলছি, কীভাবে সাহায্য করতে পারি?',
                        'শুভ সকাল, ওয়ালটনে স্বাগতম!',
                        'হ্যালো, আমি আপনার এআই অ্যাসিস্ট্যান্ট বলছি।'
                    ])
                    ->columnSpanFull(),
                    
                TagsInput::make('behavior_rules')
                    ->label('মাঝখানের আচরণ (Behavior/Tone)')
                    ->suggestions([
                        'কাস্টমারকে সব সময় "স্যার" বা "ম্যাডাম" বলে সম্বোধন করবে',
                        'একসাথে ২টির বেশি প্রশ্ন করবে না',
                        'খুব স্মার্ট এবং কর্পোরেট টোনে কথা বলবে',
                        'মানুষের মতো স্বাভাবিকভাবে কথা বলবে, রোবটের মতো নয়'
                    ])
                    ->columnSpanFull(),
                    
                TagsInput::make('closing_rules')
                    ->label('শেষের কথা (Closing)')
                    ->suggestions([
                        'ওয়ালটনের সাথে থাকার জন্য ধন্যবাদ, আপনার দিনটি শুভ হোক।',
                        'স্যার, আমি কি আপনাকে আর কোনোভাবে সাহায্য করতে পারি?',
                        'আপনার অভিযোগটি সফলভাবে নেওয়া হয়েছে, ধন্যবাদ।'
                    ])
                    ->columnSpanFull(),

                // --- সাধারণ প্রশ্ন ও উত্তর ---
                TagsInput::make('sample_question')
                    ->label('সম্ভাব্য প্রশ্নসমূহ (Questions)')
                    ->columnSpanFull(),
                    
                TagsInput::make('answer')
                    ->label('একাধিক উত্তর (Answers)')
                    ->columnSpanFull(),

                // 🆕 প্রোডাক্ট মডেল লিস্ট
                TagsInput::make('product_models')
                    ->label('🖥️ প্রোডাক্ট মডেলসমূহ (যেমন: W32E200, W43E300)')
                    ->columnSpanFull(),

                // 🆕 ওয়ারেন্টি তথ্য
                Textarea::make('warranty_info')
                    ->label('🛡️ ওয়ারেন্টি তথ্য')
                    ->placeholder("যেমন: TV-তে ৩ বছর পার্টস ওয়ারেন্টি, ১ বছর সার্ভিস ওয়ারেন্টি।")
                    ->rows(3)
                    ->columnSpanFull(),

                // 🆕 সার্ভিস চার্জ
                Textarea::make('service_charge')
                    ->label('💰 সার্ভিস চার্জ')
                    ->placeholder("যেমন: ওয়ারেন্টি মধ্যে: বিনামূল্যে। ওয়ারেন্টির পরে: ৫০০-২০০০ টাকা।")
                    ->rows(3)
                    ->columnSpanFull(),

                // 🆕 সাধারণ সমস্যা ও সমাধান
                TagsInput::make('common_issues')
                    ->label('⚠️ সাধারণ সমস্যা ও সমাধান (যেমন: রিমোট কাজ না করলে ব্যাটারি পরিবর্তন করুন)')
                    ->columnSpanFull(),

                // --- প্রোডাক্ট ডাটা এবং রুলস ---
                TagsInput::make('mandatory_fields')
                    ->label('বাধ্যতামূলক ডাটা (Mandatory Fields)')
                    ->suggestions(['কাস্টমারের নাম', 'মোবাইল নাম্বার', 'বিকল্প নাম্বার', 'ঠিকানা', 'প্রোডাক্টের মডেল', 'সমস্যা']),
                
                TagsInput::make('strict_validation')
                    ->label('কড়া যাচাইকরণ (Strict Validation)')
                    ->suggestions(['মোবাইল নাম্বার ১১ ডিজিট হতে হবে', 'ঠিকানা সম্পূর্ণ হতে হবে']),
                    
                TagsInput::make('negative_rules')
                    ->label('যা বলা একদম নিষেধ (Negative Rules)')
                    ->suggestions([
                        'কখনোই টাকা ফেরতের প্রতিশ্রুতি দেওয়া যাবে না',
                        'অন্য কোম্পানির প্রোডাক্টের বদনাম করা যাবে না',
                        'ভুল তথ্য দেওয়া যাবে না'
                    ])
                    ->columnSpanFull(),

                TagsInput::make('escalation_rules')
                    ->label('কল ট্রান্সফার (Escalation Rules)')
                    ->suggestions([
                        'কাস্টমার গালি দিলে বা রেগে গেলে হিউম্যান এজেন্টের কাছে ট্রান্সফার করবে',
                        'মানুষের সাথে কথা বলতে চাইলে ট্রান্সফার করবে'
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('সক্রিয় (Active)')
                    ->default(true),
            ]);
    }
}