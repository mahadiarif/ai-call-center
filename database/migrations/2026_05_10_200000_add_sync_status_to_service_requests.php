<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'crm_sync_status')) {
                $table->string('crm_sync_status')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('service_requests', 'walton_sr_id')) {
                $table->string('walton_sr_id')->nullable()->after('crm_sync_status');
            }
        });
        
        if (Schema::hasTable('ai_performance_logs') && Schema::hasColumn('ai_performance_logs', 'latency_ms')) {
            Schema::table('ai_performance_logs', function (Blueprint $table) {
                $table->decimal('latency_ms', 10, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['crm_sync_status', 'walton_sr_id']);
        });
    }
};
