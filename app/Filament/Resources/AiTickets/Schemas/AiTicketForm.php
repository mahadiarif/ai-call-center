<?php

namespace App\Filament\Resources\AiTickets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;

class AiTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Customer Info - Editable for manual creation
                TextInput::make('customer_name')
                    ->label('কাস্টমারের নাম')
                    ->placeholder('কাস্টমারের নাম লিখুন')
                    ->default(fn ($record) => $record?->serviceRequest?->customer_name)
                    ->disabled(fn ($record) => $record !== null && $record->serviceRequest !== null),
                    
                TextInput::make('customer_number')
                    ->label('মোবাইল নাম্বার')
                    ->tel()
                    ->placeholder('01XXXXXXXXX')
                    ->required()
                    ->default(fn ($record) => $record?->serviceRequest?->mobile_number ?? $record?->customer_number)
                    ->disabled(fn ($record) => $record !== null && $record->serviceRequest !== null),
                    
                TextInput::make('district')
                    ->label('জেলা')
                    ->placeholder('জেলার নাম')
                    ->default(function ($record) {
                        return $record?->serviceRequest?->district 
                            ?? $record?->extracted_data['district'] 
                            ?? null;
                    }),
                    
                Textarea::make('address')
                    ->label('ঠিকানা')
                    ->placeholder('সম্পূর্ণ ঠিকানা লিখুন')
                    ->rows(2)
                    ->default(function ($record) {
                        return $record?->serviceRequest?->address 
                            ?? $record?->extracted_data['address'] 
                            ?? null;
                    }),
                    
                TextInput::make('product_name')
                    ->label('পণ্য')
                    ->placeholder('পণ্যের নাম')
                    ->default(function ($record) {
                        return $record?->serviceRequest?->product_name 
                            ?? $record?->extracted_data['product_name'] 
                            ?? null;
                    }),
                    
                TextInput::make('barcode')
                    ->label('বারকোড')
                    ->placeholder('বারকোড নম্বর')
                    ->default(function ($record) {
                        return $record?->serviceRequest?->barcode 
                            ?? $record?->extracted_data['barcode'] 
                            ?? null;
                    }),
                    
                Textarea::make('problem_description')
                    ->label('সমস্যার বিবরণ')
                    ->placeholder('সমস্যার বিস্তারিত বিবরণ')
                    ->rows(3)
                    ->default(function ($record) {
                        return $record?->serviceRequest?->problem_description 
                            ?? $record?->extracted_data['problem_description'] 
                            ?? null;
                    }),
                Select::make('status')
                    ->label('স্ট্যাটাস')
                    ->options([
                        'Pending' => 'Pending (অপেক্ষমাণ)',
                        'Solve' => 'Solve (সমাধান)',
                        'Follow' => 'Follow (ফলো-আপ প্রয়োজন)',
                    ])
                    ->required()
                    ->default('Pending'),
                
                // Existing Comments Display
                Repeater::make('comments')
                    ->label('পূর্ববর্তী মন্তব্য')
                    ->relationship()
                    ->schema([
                        TextInput::make('commenter_name')
                            ->label('নাম')
                            ->disabled(),
                        TextInput::make('commenter_mobile')
                            ->label('মোবাইল নাম্বার')
                            ->disabled(),
                        Textarea::make('comment_text')
                            ->label('মন্তব্য')
                            ->disabled()
                            ->rows(2),
                        Placeholder::make('created_at')
                            ->label('তৈরি সময়')
                            ->content(fn ($record) => $record?->created_at?->format('d-m-Y H:i') ?? ''),
                    ])
                    ->collapsed(fn ($record) => !$record || $record->comments->isEmpty())
                    ->orderColumn('created_at'),
                
                // Add New Comment Section
                TextInput::make('new_comment_name')
                    ->label('আপনার নাম')
                    ->placeholder('মন্তব্যকারীর নাম লিখুন')
                    ->visible(fn ($record) => $record !== null),
                TextInput::make('new_comment_mobile')
                    ->label('মোবাইল নাম্বার')
                    ->tel()
                    ->placeholder('০১৬XXXX XXXX')
                    ->visible(fn ($record) => $record !== null),
                Textarea::make('new_comment_text')
                    ->label('নতুন মন্তব্য')
                    ->placeholder('আপনার মন্তব্য লিখুন...')
                    ->rows(4)
                    ->visible(fn ($record) => $record !== null),
            ]);
    }
}
