<?php

namespace App\Observers;

use App\Models\AiTicket;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class AiTicketObserver
{
    /**
     * Handle the AiTicket "updated" event.
     */
    public function updated(AiTicket $aiTicket): void
    {
        // যখন AiTicket এর status বদলায়, তখন linked ServiceRequest এর status ও বদলে দাও
        if ($aiTicket->isDirty('status') && $aiTicket->serviceRequest) {
            $aiTicket->serviceRequest->update([
                'status' => $aiTicket->status
            ]);
        }

        // status পরিবর্তনে notification
        if ($aiTicket->isDirty('status')) {
            try {
                $statusLabel = match($aiTicket->status) {
                    'Solve'    => '✅ সমাধান হয়েছে',
                    'Follow'   => '🔁 ফলো-আপ দরকার',
                    'Pending'  => '⏳ Pending এ ফিরেছে',
                    default    => $aiTicket->status,
                };

                Notification::make()
                    ->title("AI টিকেট আপডেট: {$statusLabel}")
                    ->body('টিকেট #' . $aiTicket->id . ' এর স্ট্যাটাস পরিবর্তন হয়েছে')
                    ->icon('heroicon-o-ticket')
                    ->color('info')
                    ->actions([
                        Action::make('view')
                            ->label('দেখুন')
                            ->url('/admin/ai-tickets')
                            ->markAsRead(),
                    ])
                    ->sendToDatabase(User::all());
            } catch (\Throwable $e) {
                // silent fail
            }
        }
    }
}
