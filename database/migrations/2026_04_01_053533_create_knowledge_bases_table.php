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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->nullable(); 
            $table->string('category'); 
            
            $table->json('sample_question')->nullable(); 
            $table->json('answer')->nullable(); 
            
            // 🔥 এআই ব্রেইন (Persona) এর জন্য স্পেশাল ফিল্ড
            $table->json('greeting_rules')->nullable(); // শুরুতে কী বলবে
            $table->json('behavior_rules')->nullable(); // মাঝখানে কীভাবে কথা বলবে
            $table->json('closing_rules')->nullable(); // শেষে কীভাবে বিদায় নেবে
            
            // প্রোডাক্ট এবং কন্ট্রোল রুলস
            $table->json('mandatory_fields')->nullable(); 
            $table->json('strict_validation')->nullable(); 
            $table->json('special_rules')->nullable(); 
            $table->json('negative_rules')->nullable(); 
            $table->json('escalation_rules')->nullable(); 
            
            $table->string('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};