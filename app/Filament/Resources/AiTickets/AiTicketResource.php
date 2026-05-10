<?php

namespace App\Filament\Resources\AiTickets;

use App\Filament\Resources\AiTickets\Pages\CreateAiTicket;
use App\Filament\Resources\AiTickets\Pages\EditAiTicket;
use App\Filament\Resources\AiTickets\Pages\ListAiTickets;
use App\Filament\Resources\AiTickets\Pages\ViewAiTicket;
use App\Filament\Resources\AiTickets\Schemas\AiTicketForm;
use App\Filament\Resources\AiTickets\Schemas\AiTicketInfolist;
use App\Filament\Resources\AiTickets\Tables\AiTicketsTable;
use App\Models\AiTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiTicketResource extends Resource
{
    protected static ?string $model = AiTicket::class;

    protected static ?string $navigationGroup = '📞 Call Operations';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = "AI Tickets";

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AiTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiTicketsTable::configure($table);
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
            'index' => ListAiTickets::route('/'),
            'create' => CreateAiTicket::route('/create'),
            'view' => ViewAiTicket::route('/{record}'),
            'edit' => EditAiTicket::route('/{record}/edit'),
        ];
    }
}
