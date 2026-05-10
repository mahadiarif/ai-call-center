<?php
$dirs = [
    'c:/laragon/www/ai-call-center/app/Filament/Resources/SrTickets',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/SrTickets/Pages',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmComplaints',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmComplaints/Pages',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmPartsQueries',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmPartsQueries/Pages',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmBillQueries',
    'c:/laragon/www/ai-call-center/app/Filament/Resources/QmBillQueries/Pages',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created: $dir\n";
    } else {
        echo "Exists: $dir\n";
    }
}
echo "Done!\n";
