<?php

namespace App\Filament\Resources\IvrServices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class IvrServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key_press')
                    ->required(),
                TextInput::make('service_name')
                    ->required(),
                Textarea::make('system_prompt')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('required_fields')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('serial_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('voice_gender')
                    ->label('AI কণ্ঠস্বর (Gender)')
                    ->options([
                        'Charon' => 'পুরুষ (Male)',
                        'Radha' => 'নারী (Female)',
                    ])
                    ->default('Charon'),
                TextInput::make('voice_speed')
                    ->label('কণ্ঠস্বর গতি (Speed)')
                    ->numeric()
                    ->step(0.1)
                    ->default(1.0)
                    ->minValue(0.25)
                    ->maxValue(2.0)
                    ->helperText('0.25 (খুব ধীর) থেকে 2.0 (খুব দ্রুত) - 1.0 সাধারণ গতি'),
            ]);
    }
}
