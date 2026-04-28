<?php

namespace App\Filament\Resources\AiTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Schema;

class AiTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('serviceRequest.customer_name')
                    ->label('কাস্টমারের নাম')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.mobile_number')
                    ->label('মোবাইল নাম্বার')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.district')
                    ->label('জেলা')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.address')
                    ->label('ঠিকানা')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.product_name')
                    ->label('পণ্য')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.barcode')
                    ->label('বারকোড')
                    ->placeholder('N/A'),
                TextEntry::make('serviceRequest.problem_description')
                    ->label('সমস্যার বিবরণ')
                    ->placeholder('N/A'),
                TextEntry::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Solve' => 'success',
                            'Follow' => 'warning',
                            'Pending' => 'info',
                            default => 'secondary',
                        };
                    }),
                TextEntry::make('created_at')
                    ->label('তৈরি')
                    ->dateTime()
                    ->placeholder('N/A'),
                TextEntry::make('updated_at')
                    ->label('আপডেট')
                    ->dateTime()
                    ->placeholder('N/A'),
                RepeatableEntry::make('comments')
                    ->label('মন্তব্য সমূহ')
                    ->schema([
                        TextEntry::make('commenter_name')
                            ->label('নাম'),
                        TextEntry::make('commenter_mobile')
                            ->label('মোবাইল'),
                        TextEntry::make('comment_text')
                            ->label('মন্তব্য')
                            ->prose(),
                        TextEntry::make('created_at')
                            ->label('সময়')
                            ->dateTime()
                            ->size('sm'),
                    ]),
            ]);
    }
}
