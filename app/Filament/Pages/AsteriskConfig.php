<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class AsteriskConfig extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string | \UnitEnum | null $navigationGroup = '🛡️ System Management';
    protected static ?string $navigationLabel = 'Asterisk Config';
    protected string $view = 'filament.pages.asterisk-config';
    protected static ?int $navigationSort = 100;

    public $sipConfig;
    public $extensionsConfig;
    public $pjsipConfig;
    
    protected $sipPath = '/etc/asterisk/sip.conf';
    protected $extensionsPath = '/etc/asterisk/extensions.conf';
    protected $pjsipPath = '/etc/asterisk/pjsip.conf';

    public function mount()
    {
        $this->loadConfigs();
    }

    public function loadConfigs()
    {
        $this->sipConfig = $this->readFile($this->sipPath, 'sip.conf');
        $this->extensionsConfig = $this->readFile($this->extensionsPath, 'extensions.conf');
        $this->pjsipConfig = $this->readFile($this->pjsipPath, 'pjsip.conf');
    }

    protected function readFile($path, $name)
    {
        if (File::exists($path) && File::isReadable($path)) {
            return File::get($path);
        }
        return "; [ERROR] {$name} is not readable or not found. \n; Run: sudo chown www-data:www-data {$path} && sudo chmod 664 {$path}";
    }

    public function saveConfigs()
    {
        try {
            File::put($this->sipPath, $this->sipConfig);
            File::put($this->extensionsPath, $this->extensionsConfig);
            File::put($this->pjsipPath, $this->pjsipConfig);
            Notification::make()->title('Configs Saved Successfully!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Error Saving Configs')->body($e->getMessage())->danger()->send();
        }
    }

    public function reloadAsterisk($type = 'all')
    {
        $cmd = match($type) {
            'sip' => 'sip reload',
            'pjsip' => 'pjsip reload',
            'extensions' => 'dialplan reload',
            default => 'core reload',
        };
        shell_exec("sudo asterisk -rx \"{$cmd}\"");
        Notification::make()->title(ucfirst($type) . ' Reloaded!')->success()->send();
    }
}
