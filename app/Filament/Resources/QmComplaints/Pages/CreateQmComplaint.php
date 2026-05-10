<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQmComplaint extends CreateRecord
{
    protected static string $resource = QmComplaintResource::class;
}