<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_bill_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_service_id')->nullable()->constrained('ivr_services')->nullOnDelete();

            // QM Reference
            $table->string('qm_number')->nullable()->unique()->comment('QM-XXXX format');
            $table->string('sr_reference')->nullable()->comment('SR number the bill query is about');

            // Customer info
            $table->string('mobile_number')->index();
            $table->string('customer_name')->nullable();
            $table->string('alt_mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // Bill-specific fields
            $table->string('product_name')->nullable();
            $table->text('bill_query_details')->nullable()->comment('বিল সংক্রান্ত প্রশ্ন');

            // Meta
            $table->text('comments')->nullable();
            $table->text('call_transcript')->nullable();
            $table->json('extracted_data')->nullable();
            $table->string('call_recording')->nullable();

            // Status
            $table->enum('status', [
                'Incoming', 'Pending', 'Under Review',
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
        Schema::dropIfExists('qm_bill_queries');
    }
};
