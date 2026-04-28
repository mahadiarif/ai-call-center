<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name'),
                TextInput::make('mobile_number'),
                TextInput::make('alt_mobile_number'),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('district'),
                TextInput::make('product_name'),
                TextInput::make('barcode'),
                Textarea::make('problem_description')
                    ->columnSpanFull(),
                Textarea::make('call_transcript')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('Pending'),
            ]);
    }
}
