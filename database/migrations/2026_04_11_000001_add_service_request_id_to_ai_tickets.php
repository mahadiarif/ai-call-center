<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_tickets', 'service_request_id')) {
                $table->unsignedBigInteger('service_request_id')->nullable()->after('id');
                $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('ai_tickets', 'service_request_id')) {
                $table->dropForeign(['service_request_id']);
                $table->dropColumn('service_request_id');
            }
        });
    }
};
