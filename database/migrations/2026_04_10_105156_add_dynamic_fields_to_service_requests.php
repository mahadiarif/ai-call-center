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
        Schema::table('service_requests', function (Blueprint $table) {
            // ডাটাবেসে কলামগুলো না থাকলে তৈরি করবে
            if (!Schema::hasColumn('service_requests', 'ivr_service_id')) {
                $table->unsignedBigInteger('ivr_service_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('service_requests', 'extracted_data')) {
                $table->json('extracted_data')->nullable()->after('ivr_service_id');
            }
            if (!Schema::hasColumn('service_requests', 'call_transcript')) {
                $table->text('call_transcript')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            //
        });
    }
};
