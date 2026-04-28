<?php

namespace App\Filament\Resources\AiTickets\Pages;

use App\Filament\Resources\AiTickets\AiTicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiTicket extends CreateRecord
{
    protected static string $resource = AiTicketResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract fields that go into extracted_data
        $extractedData = [
            'customer_name' => $data['customer_name'] ?? null,
            'district' => $data['district'] ?? null,
            'address' => $data['address'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'problem_description' => $data['problem_description'] ?? null,
        ];
        
        // Remove these from main data and put in extracted_data
        unset(
            $data['customer_name'],
            $data['district'],
            $data['address'],
            $data['product_name'],
            $data['barcode'],
            $data['problem_description']
        );
        
        // Add extracted_data
        $data['extracted_data'] = array_filter($extractedData); // Remove null values
        
        // Set ivr_service_id default if not set
        if (empty($data['ivr_service_id'])) {
            $data['ivr_service_id'] = 1; // Default IVR
        }
        
        return $data;
    }
}
