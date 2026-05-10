<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SMS Gateway Settings
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name')->default('Gennet');
            $table->string('gateway_url')->default('https://isms.gennet.com.bd/api/v3/send-sms');
            $table->string('api_token')->nullable();
            $table->string('sid')->nullable();                    // Sender ID / Masking number
            $table->enum('sms_type', ['masking', 'non_masking'])->default('non_masking');
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_send_on_sr')->default(true);    // SR তৈরিতে auto SMS
            $table->boolean('auto_send_on_qm')->default(false);   // QM তৈরিতে auto SMS
            $table->text('sr_template')->nullable();              // Custom SR SMS template
            $table->text('qm_template')->nullable();              // Custom QM SMS template
            $table->timestamps();
        });

        // SMS Logs
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mobile');
            $table->text('message');
            $table->enum('type', ['auto', 'manual'])->default('auto');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->string('reference_type')->nullable();         // 'sr_ticket' or 'qm_ticket'
            $table->unsignedBigInteger('reference_id')->nullable(); // ticket ID
            $table->string('walton_sr')->nullable();              // Walton SR number
            $table->json('gateway_response')->nullable();
            $table->string('sent_by')->default('system');         // system or admin name
            $table->timestamps();
        });

        // Default settings insert
        DB::table('sms_settings')->insert([
            'gateway_name'      => 'Gennet',
            'gateway_url'       => 'https://isms.gennet.com.bd/api/v3/send-sms',
            'api_token'         => null,
            'sid'               => null,
            'sms_type'          => 'non_masking',
            'is_active'         => false,
            'auto_send_on_sr'   => true,
            'auto_send_on_qm'   => false,
            'sr_template'       => 'প্রিয় {name}, আপনার ওয়ালটন সার্ভিস রিকোয়েস্ট নম্বর: {sr_number} ({product})। এই নম্বরটি সেভ করে রাখুন। ধন্যবাদ। -Walton BD',
            'qm_template'       => 'প্রিয় {name}, আপনার ওয়ালটন QM টিকেট নম্বর: {qm_number} গ্রহণ করা হয়েছে। শীঘ্রই যোগাযোগ করা হবে। ধন্যবাদ। -Walton BD',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_settings');
    }
};
