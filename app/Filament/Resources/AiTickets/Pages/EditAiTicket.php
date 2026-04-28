<?php

namespace App\Filament\Resources\AiTickets\Pages;

use App\Filament\Resources\AiTickets\AiTicketResource;
use App\Models\AiTicketComment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAiTicket extends EditRecord
{
    protected static string $resource = AiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load extracted_data into form fields for editing
        if (!empty($data['extracted_data'])) {
            $extracted = $data['extracted_data'];
            $data['customer_name'] = $extracted['customer_name'] ?? null;
            $data['district'] = $extracted['district'] ?? null;
            $data['address'] = $extracted['address'] ?? null;
            $data['product_name'] = $extracted['product_name'] ?? null;
            $data['barcode'] = $extracted['barcode'] ?? null;
            $data['problem_description'] = $extracted['problem_description'] ?? null;
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
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
        
        // Merge with existing extracted_data
        $data['extracted_data'] = array_merge(
            $data['extracted_data'] ?? [],
            array_filter($extractedData) // Remove null values
        );
        
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // যদি নতুন মন্তব্য থাকে, তাহলে সেটা সংরক্ষণ করো
        if (!empty($data['new_comment_text'])) {
            AiTicketComment::create([
                'ai_ticket_id' => $this->record->id,
                'commenter_name' => $data['new_comment_name'] ?? 'Anonymous',
                'commenter_mobile' => $data['new_comment_mobile'] ?? null,
                'comment_text' => $data['new_comment_text'],
            ]);
        }
    }
}

