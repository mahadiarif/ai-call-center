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
        Schema::create('ivr_services', function (Blueprint $table) {
            $table->id();
            $table->string('key_press')->unique(); // যেমন: 1, 2, 3
            $table->string('service_name'); // যেমন: এসি কমপ্লেইন, প্রোডাক্ট ইনফো
            $table->text('system_prompt'); // এআই-এর ব্রেইনের জন্য স্পেসিফিক কমান্ড
            $table->json('required_fields'); // এই সার্ভিসের জন্য কী কী ফিল্ড লাগবে তার লিস্ট (JSON)
            $table->boolean('is_active')->default(true); // অন/অফ করার সুইচ
            $table->integer('serial_order')->default(0); // সিরিয়াল মেইনটেইন করার জন্য
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ivr_services');
    }
};