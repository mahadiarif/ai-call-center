<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use App\Models\SmsSetting;

class SmsSettings extends Page
{
    protected string $view = 'filament.pages.sms-settings';
    protected static ?string $navigationLabel = '📱 SMS সেটিংস';
    protected static \UnitEnum|string|null $navigationGroup = 'AI সেটিংস';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;
    protected static ?int $navigationSort = 30;

    public string  $gateway_name    = 'Gennet';
    public string  $gateway_url     = 'https://isms.gennet.com.bd/api/v3/send-sms';
    public ?string $api_token       = null;
    public ?string $sid             = null;
    public string  $sms_type        = 'non_masking';
    public bool    $is_active       = false;
    public bool    $auto_send_on_sr = true;
    public bool    $auto_send_on_qm = false;
    public string  $sr_template     = 'প্রিয় {name}, আপনার ওয়ালটন SR নম্বর: {sr_number} ({product})। এই নম্বরটি সেভ করুন। -Walton BD';
    public string  $qm_template     = 'প্রিয় {name}, আপনার QM টিকেট {qm_number} গ্রহণ করা হয়েছে। -Walton BD';
    public ?string $test_mobile     = null;

    public function mount(): void
    {
        $s = SmsSetting::current();
        $this->gateway_name    = $s->gateway_name    ?? 'Gennet';
        $this->gateway_url     = $s->gateway_url     ?? 'https://isms.gennet.com.bd/api/v3/send-sms';
        $this->api_token       = $s->api_token;
        $this->sid             = $s->sid;
        $this->sms_type        = $s->sms_type        ?? 'non_masking';
        $this->is_active       = (bool)($s->is_active       ?? false);
        $this->auto_send_on_sr = (bool)($s->auto_send_on_sr ?? true);
        $this->auto_send_on_qm = (bool)($s->auto_send_on_qm ?? false);
        $this->sr_template     = $s->sr_template     ?? 'প্রিয় {name}, আপনার ওয়ালটন SR নম্বর: {sr_number} ({product})। এই নম্বরটি সেভ করুন। -Walton BD';
        $this->qm_template     = $s->qm_template     ?? 'প্রিয় {name}, আপনার QM টিকেট {qm_number} গ্রহণ করা হয়েছে। -Walton BD';
    }

    public function save(): void
    {
        SmsSetting::current()->update([
            'gateway_name'    => $this->gateway_name,
            'gateway_url'     => $this->gateway_url,
            'api_token'       => $this->api_token,
            'sid'             => $this->sid,
            'sms_type'        => $this->sms_type,
            'is_active'       => $this->is_active,
            'auto_send_on_sr' => $this->auto_send_on_sr,
            'auto_send_on_qm' => $this->auto_send_on_qm,
            'sr_template'     => $this->sr_template,
            'qm_template'     => $this->qm_template,
        ]);
        Notification::make()->title('✅ SMS Settings সেভ হয়েছে!')->success()->send();
    }

    public function sendTestSms(): void
    {
        if (!$this->test_mobile) {
            Notification::make()->title('⚠️ Test mobile number দিন')->warning()->send();
            return;
        }
        $result = \App\Services\SmsService::sendManual(
            $this->test_mobile,
            'এটি একটি Walton AI Call Center Test SMS। Gateway সঠিকভাবে কাজ করছে। -Walton BD',
            '', 0, 'admin-test'
        );
        if ($result['success']) {
            Notification::make()->title('✅ Test SMS পাঠানো হয়েছে!')->success()->send();
        } else {
            Notification::make()->title('❌ ' . $result['message'])->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('💾 সেভ করুন')
                ->color('success')
                ->action('save'),
            Action::make('test')
                ->label('📨 Test SMS পাঠান')
                ->color('info')
                ->action('sendTestSms'),
        ];
    }
}
