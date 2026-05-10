<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class AsteriskConfig extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = '🛡️ System Management';
    protected static ?string $navigationLabel = 'Asterisk Config';
    protected static string $view = 'filament.pages.asterisk-config';
    protected static ?int $navigationSort = 100;

    public $sipConfig;
    public $extensionsConfig;
    
    // ফাইল পাথগুলো এখানে সেট করুন
    protected $sipPath = '/etc/asterisk/sip.conf';
    protected $extensionsPath = '/etc/asterisk/extensions.conf';

    public function mount()
    {
        // ফাইল না থাকলে .env বা ডিফল্ট পাথ চেক করার অপশন রাখা যেতে পারে
        $this->sipPath = config('asterisk.sip_path', $this->sipPath);
        $this->extensionsPath = config('asterisk.extensions_path', $this->extensionsPath);

        $this->loadConfigs();
    }

    public function loadConfigs()
    {
        $this->sipConfig = File::exists($this->sipPath) ? File::get($this->sipPath) : "; sip.conf not found at {$this->sipPath}";
        $this->extensionsConfig = File::exists($this->extensionsPath) ? File::get($this->extensionsPath) : "; extensions.conf not found at {$this->extensionsPath}";
    }

    public function saveConfigs()
    {
        try {
            if (!is_writable(dirname($this->sipPath)) || (File::exists($this->sipPath) && !is_writable($this->sipPath))) {
                throw new \Exception("File or Directory is not writable. Run: sudo chown www-data:www-data {$this->sipPath}");
            }

            File::put($this->sipPath, $this->sipConfig);
            File::put($this->extensionsPath, $this->extensionsConfig);

            Notification::make()->title('Configs Saved Successfully!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Error Saving Configs')->body($e->getMessage())->danger()->send();
        }
    }

    public function reloadAsterisk($type = 'all')
    {
        try {
            if ($type === 'sip') {
                shell_exec('sudo asterisk -rx "sip reload"');
                Notification::make()->title('SIP Reloaded!')->success()->send();
            } elseif ($type === 'extensions') {
                shell_exec('sudo asterisk -rx "dialplan reload"');
                Notification::make()->title('Dialplan Reloaded!')->success()->send();
            } else {
                shell_exec('sudo asterisk -rx "core reload"');
                Notification::make()->title('Asterisk Fully Reloaded!')->success()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Reload Failed')->body('Check sudo permissions for www-data.')->danger()->send();
        }
    }
}
