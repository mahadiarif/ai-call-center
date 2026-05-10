<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeClientApiDirsCommand extends Command
{
    protected $signature   = 'make:client-api-dirs';
    protected $description = 'Create Filament resource directories for ClientApiIntegrations';

    public function handle(): int
    {
        $dirs = [
            app_path('Filament/Resources/ClientApiIntegrations'),
            app_path('Filament/Resources/ClientApiIntegrations/Pages'),
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->info("Created: {$dir}");
            } else {
                $this->line("Exists:  {$dir}");
            }
        }
        return self::SUCCESS;
    }
}
