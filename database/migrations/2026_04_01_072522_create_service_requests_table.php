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
    Schema::create('service_requests', function (Blueprint $table) {
        $table->id();
        $table->string('customer_name')->nullable();
        $table->string('mobile_number')->nullable();
        $table->string('alt_mobile_number')->nullable();
        $table->text('address')->nullable();
        $table->string('district')->nullable();
        $table->string('product_name')->nullable();
        $table->string('service_center')->nullable();
        $table->string('barcode')->nullable();
        $table->text('problem_description')->nullable();
        $table->longText('call_transcript')->nullable(); // Full conversation records
        $table->string('status')->default('Pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
