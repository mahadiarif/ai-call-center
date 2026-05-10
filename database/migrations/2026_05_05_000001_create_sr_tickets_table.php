<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sr_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_service_id')->nullable()->constrained('ivr_services')->nullOnDelete();

            // Customer info
            $table->string('mobile_number')->index();
            $table->string('customer_name')->nullable();
            $table->string('alt_mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // SR-specific fields
            $table->string('product_name')->nullable();
            $table->string('product_model')->nullable();
            $table->string('barcode')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('problem_description')->nullable();
            $table->string('service_center')->nullable();
            $table->string('brand')->nullable()->default('WALTON');

            // Meta
            $table->text('comments')->nullable();
            $table->text('call_transcript')->nullable();
            $table->json('extracted_data')->nullable();
            $table->string('call_recording')->nullable();

            // Status
            $table->enum('status', [
                'Incoming', 'Pending', 'In Progress',
                'Resolved', 'Escalation Requested', 'Drop Call', 'Rejected'
            ])->default('Pending');

            // Future API integration fields
            $table->string('client_sr_id')->nullable()->comment('Client-er actual SR ID (API connect korar pore)');
            $table->timestamp('synced_at')->nullable()->comment('Client database-e sync hoar time');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sr_tickets');
    }
};
