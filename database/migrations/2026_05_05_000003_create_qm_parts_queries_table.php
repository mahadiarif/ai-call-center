<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_parts_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_service_id')->nullable()->constrained('ivr_services')->nullOnDelete();

            // QM Reference
            $table->string('qm_number')->nullable()->unique()->comment('QM-XXXX format');

            // Customer info
            $table->string('mobile_number')->index();
            $table->string('customer_name')->nullable();
            $table->string('alt_mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // Parts-specific fields
            $table->string('product_name')->nullable();
            $table->string('product_model')->nullable();
            $table->text('parts_name')->nullable()->comment('কোন পার্টস দরকার');
            $table->string('preferred_service_point')->nullable()->comment('পছন্দের সার্ভিস পয়েন্ট');

            // Meta
            $table->text('comments')->nullable();
            $table->text('call_transcript')->nullable();
            $table->json('extracted_data')->nullable();
            $table->string('call_recording')->nullable();

            // Status
            $table->enum('status', [
                'Incoming', 'Pending', 'Processing',
                'Resolved', 'Rejected', 'Drop Call'
            ])->default('Pending');

            // Future API integration
            $table->string('client_qm_id')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_parts_queries');
    }
};
