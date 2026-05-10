<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make mobile_number nullable on all ticket tables — some calls may not provide it
        if (Schema::hasTable('sr_tickets')) {
            Schema::table('sr_tickets', function (Blueprint $table) {
                $table->string('mobile_number')->nullable()->change();
            });
        }
        if (Schema::hasTable('qm_complaints')) {
            Schema::table('qm_complaints', function (Blueprint $table) {
                $table->string('mobile_number')->nullable()->change();
            });
        }
        if (Schema::hasTable('qm_parts_queries')) {
            Schema::table('qm_parts_queries', function (Blueprint $table) {
                $table->string('mobile_number')->nullable()->change();
            });
        }
        if (Schema::hasTable('qm_bill_queries')) {
            Schema::table('qm_bill_queries', function (Blueprint $table) {
                $table->string('mobile_number')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Revert (only if all records have mobile_number)
    }
};
