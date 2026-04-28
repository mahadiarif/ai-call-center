<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('caller_number')->nullable();          // কোন নম্বর থেকে কল এসেছে
            $table->unsignedBigInteger('ivr_service_id')->nullable(); // কোন IVR তে গেছে
            $table->integer('duration')->default(0);              // কতক্ষণ কথা হয়েছে (সেকেন্ড)
            $table->string('recording_path')->nullable();         // কল রেকর্ডিং ফাইল পাথ
            $table->string('status')->default('completed');       // completed / missed / dropped
            $table->string('session_id')->nullable();             // ফ্রন্টএন্ডের session id
            $table->timestamps();

            $table->foreign('ivr_service_id')->references('id')->on('ivr_services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
