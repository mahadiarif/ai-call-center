<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id')->nullable(); // কোন service request এর
            $table->unsignedBigInteger('ivr_service_id')->nullable();     // কোন IVR এর
            $table->integer('total_fields')->default(0);   // কতটা field collect করতে হতো
            $table->integer('filled_fields')->default(0);  // কতটা successfully collect হয়েছে
            $table->integer('score')->default(0);          // score (0-100)
            $table->json('missing_fields')->nullable();    // কোন কোন field miss হয়েছে
            $table->json('collected_fields')->nullable();  // কোন কোন field পেয়েছে
            $table->integer('call_duration')->default(0);  // কল কতক্ষণ ছিল (সেকেন্ড)
            $table->timestamps();

            $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete('cascade');
            $table->foreign('ivr_service_id')->references('id')->on('ivr_services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_performance_logs');
    }
};
