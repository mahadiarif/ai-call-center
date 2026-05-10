<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('qm_number')->unique()->nullable();
            $table->unsignedBigInteger('ivr_service_id')->nullable();

            // Customer info
            $table->string('customer_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('alt_mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // Walton CRM fields
            $table->string('query_type')->default('General Inquiry');
            // General Inquiry, Product Inquiry, Parts Inquiry, Bill Inquiry,
            // Technical Support, Complain, Price Inquiry, Online Sell, DCAMP, TV Activation

            $table->string('brand')->default('WALTON');
            // WALTON, MARCEL, ORIGIN, SAFE, OTHERS

            $table->string('product')->nullable();

            $table->string('related')->default('WSMS');
            // WSMS, Call Center, R&D, PLAZA, Marketing, Sourcing, Distributor, Admin, Offer, Others

            $table->string('subject')->nullable();
            // Complain Subject, WSMS Expert Behave, Expert Qualification, etc.

            $table->boolean('send_to_mail')->default(false);
            $table->boolean('send_via_sms')->default(false);

            $table->string('send_to_group')->nullable();
            // Tech Support, Parts Query, Bill Query, Complain

            $table->text('message')->nullable(); // main details / description

            $table->text('remarks')->nullable();

            // System fields
            $table->string('status')->default('Incoming');
            // Incoming, Pending, Under Review, Resolved, Rejected, Drop Call

            $table->text('call_transcript')->nullable();
            $table->text('comments')->nullable();
            $table->string('call_recording')->nullable();
            $table->json('extracted_data')->nullable();

            // API sync
            $table->string('client_qm_id')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->foreign('ivr_service_id')->references('id')->on('ivr_services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_tickets');
    }
};
