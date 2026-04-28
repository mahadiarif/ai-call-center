<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            $table->string('ai_name')->nullable()->after('service_name');
            $table->text('greeting_message')->nullable()->after('ai_name');
        });
    }

    public function down(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            $table->dropColumn(['ai_name', 'greeting_message']);
        });
    }
};