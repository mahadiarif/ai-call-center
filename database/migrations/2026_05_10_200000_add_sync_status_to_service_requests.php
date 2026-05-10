<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('crm_sync_status')->default('pending')->after('status');
            $table->string('walton_sr_id')->nullable()->after('crm_sync_status');
        });
        
        Schema::table('ai_performance_logs', function (Blueprint $table) {
            $table->decimal('latency_ms', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['crm_sync_status', 'walton_sr_id']);
        });
    }
};
