<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // IVR সার্ভিসের ক্যাটাগরি বোঝার জন্য
            $table->foreignId('ivr_service_id')->nullable()->after('id')->constrained('ivr_services')->onDelete('cascade');
            
            // এআইয়ের নেওয়া ডায়নামিক প্রশ্নের উত্তরগুলো রাখার জন্য
            $table->json('extracted_data')->nullable()->after('problem_description');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['ivr_service_id']);
            $table->dropColumn(['ivr_service_id', 'extracted_data']);
        });
    }
};