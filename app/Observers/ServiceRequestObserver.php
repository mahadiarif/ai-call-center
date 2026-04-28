<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Models\AiTicket;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class ServiceRequestObserver
{
    // নতুন সার্ভিস রিকোয়েস্ট এলে সব admin কে real-time notification
    public function created(ServiceRequest $serviceRequest): void
    {
        try {
            $name   = $serviceRequest->customer_name ?? 'অজানা কাস্টমার';
            $mobile = $serviceRequest->mobile_number  ?? 'নম্বর নেই';

            $recipients = User::all();

            Notification::make()
                ->title('🆕 নতুন সার্ভিস রিকোয়েস্ট!')
                ->body("{$name} ({$mobile}) — নতুন রিকোয়েস্ট এসেছে")
                ->icon('heroicon-o-phone-arrow-down-left')
                ->color('success')
                ->actions([
                    Action::make('view')
                        ->label('দেখুন')
                        ->url('/admin/service-requests')
                        ->markAsRead(),
                ])
                ->sendToDatabase($recipients);
        } catch (\Throwable $e) {
            // notification fail হলেও মূল save বন্ধ না হোক
        }
    }

    /**
     * Handle the ServiceRequest "updated" event.
     */
    public function updated(ServiceRequest $serviceRequest): void
    {
        // যদি status "Resolved" হয়, তাহলে AiTicket তৈরি করো
        if ($serviceRequest->status === 'Resolved') {
            // চেক করো যে এই ServiceRequest এর জন্য ইতিমধ্যে AiTicket আছে কি না
            $existingTicket = AiTicket::where('service_request_id', $serviceRequest->id)->first();
            
            if (!$existingTicket) {
                // নতুন AiTicket তৈরি করো
                AiTicket::create([
                    'service_request_id' => $serviceRequest->id,
                    'ivr_service_id' => $serviceRequest->ivr_service_id ?? 1, // ডিফল্ট ভ্যালু
                    'customer_number' => $serviceRequest->mobile_number,
                    'extracted_data' => [
                        'customer_name' => $serviceRequest->customer_name,
                        'product_name' => $serviceRequest->product_name,
                        'problem_description' => $serviceRequest->problem_description,
                        'address' => $serviceRequest->address,
                        'district' => $serviceRequest->district,
                        'barcode' => $serviceRequest->barcode,
                    ],
                    'status' => 'Resolved',
                ]);
            } else {
                // যদি ইতিমধ্যে থাকে, তাহলে আপডেট করো
                $existingTicket->update([
                    'status' => 'Resolved',
                    'extracted_data' => [
                        'customer_name' => $serviceRequest->customer_name,
                        'product_name' => $serviceRequest->product_name,
                        'problem_description' => $serviceRequest->problem_description,
                        'address' => $serviceRequest->address,
                        'district' => $serviceRequest->district,
                        'barcode' => $serviceRequest->barcode,
                    ],
                ]);
            }
        }
    }
}
