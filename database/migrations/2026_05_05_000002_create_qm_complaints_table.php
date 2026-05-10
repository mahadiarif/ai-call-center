<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_service_id')->nullable()->constrained('ivr_services')->nullOnDelete();

            // QM Reference
            $table->string('qm_number')->nullable()->unique()->comment('QM-XXXX format');
            $table->string('sr_reference')->nullable()->comment('Previous SR number from client system');

            // Customer info
            $table->string('mobile_number')->index();
            $table->string('customer_name')->nullable();
            $table->string('alt_mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // Complaint-specific fields
            $table->enum('complaint_category', [
                'service_expert', 'showroom', 'product_quality', 'billing', 'other'
            ])->nullable()->comment('অভিযোগের ধরন');
            $table->string('person_name')->nullable()->comment('অভিযোগকৃত ব্যক্তির নাম');
            $table->string('showroom_address')->nullable()->comment('শো-রুম বা এলাকার নাম');
            $table->string('incident_date')->nullable()->comment('ঘটনার তারিখ');
            $table->text('complaint_details')->nullable()->comment('সম্পূর্ণ অভিযোগ বিবরণ');

            // Meta
            $table->text('comments')->nullable();
            $table->text('call_transcript')->nullable();
            $table->json('extracted_data')->nullable();
            $table->string('call_recording')->nullable();

            // Status
            $table->enum('status', [
                'Incoming', 'Pending', 'Under Review',
                'Resolved', 'Rejected', 'Escalation Requested', 'Drop Call'
            ])->default('Pending');

            // Future API integration
            $table->string('client_qm_id')->nullable()->comment('Client QM system ID');
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_complaints');
    }
};
