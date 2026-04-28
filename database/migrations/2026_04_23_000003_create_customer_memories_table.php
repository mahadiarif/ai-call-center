<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_memories', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique(); // কাস্টমারের নম্বর — unique key
            $table->string('name')->nullable();       // AI যে নাম শিখেছে
            $table->integer('total_calls')->default(0); // মোট কতবার call করেছে
            $table->timestamp('last_call_at')->nullable(); // শেষ কল কখন
            $table->string('last_ivr_service')->nullable(); // শেষবার কোন সার্ভিসে গিয়েছিল
            $table->json('known_data')->nullable();   // AI যা জানে — address, district ইত্যাদি
            $table->text('notes')->nullable();        // Agent এর নোট
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_memories');
    }
};
