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
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->label('কোম্পানির নাম');
            $table->text('about_company')->nullable()->label('কোম্পানির প্রোফাইল');
            $table->string('greeting_behavior')->default('ai_first')->label('গ্রিটিংস কন্ট্রোল');
            $table->json('dynamic_instructions')->nullable()->label('মাল্টিপল ডাইনামিক রুলস');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
