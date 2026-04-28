<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // কোন IVR সার্ভিসের জন্য এই KB — null মানে সব সার্ভিসে কাজ করবে
            $table->unsignedBigInteger('ivr_service_id')->nullable()->after('brand_name');

            // প্রোডাক্ট-স্পেসিফিক নতুন ফিল্ড
            $table->json('product_models')->nullable()->after('answer');   // কোন কোন মডেল আছে
            $table->text('warranty_info')->nullable()->after('product_models');   // ওয়ারেন্টি তথ্য
            $table->text('service_charge')->nullable()->after('warranty_info');   // সার্ভিস চার্জ
            $table->json('common_issues')->nullable()->after('service_charge');   // সাধারণ সমস্যা ও সমাধান

            $table->foreign('ivr_service_id')->references('id')->on('ivr_services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropForeign(['ivr_service_id']);
            $table->dropColumn(['ivr_service_id', 'product_models', 'warranty_info', 'service_charge', 'common_issues']);
        });
    }
};
