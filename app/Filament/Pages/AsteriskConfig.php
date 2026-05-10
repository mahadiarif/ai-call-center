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
    
    // Asterisk Stats
    public $asteriskUptime = 'N/A';
    public $activeChannels = 0;
    public $sipPeers = 0;
    public $pjsipEndpoints = 0;
    public $isAsteriskRunning = false;
    
    protected $sipPath = '/etc/asterisk/sip.conf';
    protected $extensionsPath = '/etc/asterisk/extensions.conf';
    protected $pjsipPath = '/etc/asterisk/pjsip.conf';

    public function mount()
    {
        $this->loadConfigs();
        $this->fetchAsteriskStats();
    }

    public function fetchAsteriskStats()
    {
        try {
            // Check if Asterisk is running
            $status = shell_exec('pgrep asterisk');
            $this->isAsteriskRunning = !empty($status);

            if ($this->isAsteriskRunning) {
                // Active Channels
                $channels = shell_exec("sudo asterisk -rx \"core show channels count\" | grep \"active channel\" | cut -d' ' -f1");
                $this->activeChannels = trim($channels) ?: 0;

                // Uptime
                $uptime = shell_exec("sudo asterisk -rx \"core show uptime seconds\" | grep \"System uptime\" | cut -d':' -f2");
                $this->asteriskUptime = $this->formatUptime(trim($uptime));

                // SIP Peers
                $sip = shell_exec("sudo asterisk -rx \"sip show peers\" | grep \"sip peers\" | cut -d' ' -f1");
                $this->sipPeers = trim($sip) ?: 0;

                // PJSIP Endpoints
                $pjsip = shell_exec("sudo asterisk -rx \"pjsip show endpoints\" | grep \"Objects found\" | cut -d':' -f2");
                $this->pjsipEndpoints = trim($pjsip) ?: 0;
            }
        } catch (\Exception $e) {
            // Fallback for local development or permission issues
        }
    }

    protected function formatUptime($seconds)
    {
        if (!$seconds || !is_numeric($seconds)) return 'N/A';
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a d, %h h, %i m');
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
