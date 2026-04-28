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
        // 🚀 এই লাইনটা পুরনো টেবিল থাকলে ডিলিট করে দেবে
        Schema::dropIfExists('ai_tickets');

        Schema::create('ai_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ivr_service_id'); // কোন সার্ভিসের আন্ডারে টিকিট হলো
            $table->string('customer_number')->nullable(); // কাস্টমারের নাম্বার
            $table->json('extracted_data'); // এআই যে কাস্টম ডাটাগুলো বের করবে সেটা এখানে JSON আকারে থাকবে
            $table->string('status')->default('Pending');
            $table->timestamps();

            // রিলেশনশিপ
            $table->foreign('ivr_service_id')->references('id')->on('ivr_services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tickets');
    }
};