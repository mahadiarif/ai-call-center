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
        // service_requests টেবিলের সব টেক্সট ফিল্ডকে nullable করো
        Schema::table('service_requests', function (Blueprint $table) {
            // নিশ্চিত করো যে সব ফিল্ড nullable
            try {
                $table->string('customer_name')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->string('mobile_number')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->string('alt_mobile_number')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->text('address')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->string('district')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->string('product_name')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->string('barcode')->nullable()->change();
            } catch (\Exception $e) {}
            
            try {
                $table->text('problem_description')->nullable()->change();
            } catch (\Exception $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
