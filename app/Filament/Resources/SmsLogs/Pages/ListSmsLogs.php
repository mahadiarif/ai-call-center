<?php

namespace App\Filament\Resources\SmsLogs\Pages;

use App\Filament\Resources\SmsLogs\SmsLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ListSmsLogs extends ListRecords
{
    protected static string $resource = SmsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_manual')
                ->label('📨 Manual SMS পাঠান')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    TextInput::make('mobile')
                        ->label('মোবাইল নম্বর')
                        ->placeholder('01XXXXXXXXX')
                        ->required(),
                    Textarea::make('message')
                        ->label('বার্তা')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $result = \App\Services\SmsService::sendManual(
                        $data['mobile'],
                        $data['message'],
                        '',
                        0,
                        'admin'
                    );
                    if ($result['success']) {
                        Notification::make()->title('✅ SMS পাঠানো হয়েছে!')->success()->send();
                    } else {
                        Notification::make()->title('❌ ' . $result['message'])->danger()->send();
                    }
                }),
        ];
    }
}
