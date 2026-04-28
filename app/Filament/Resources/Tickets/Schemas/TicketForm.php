<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                TextInput::make('subject'),
                Textarea::make('issue_summary')
                    ->columnSpanFull(),
                Textarea::make('conversation_history')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('resolved_by_ai'),
            ]);
    }
}
