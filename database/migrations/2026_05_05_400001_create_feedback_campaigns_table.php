<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Outbound Survey Campaigns ──────────────────────────────────────────
        Schema::create('feedback_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                          // Campaign নাম, e.g. "মে ২০২৬ SR Feedback"
            $table->text('description')->nullable();
            $table->string('status')->default('draft');      // draft | active | paused | completed
            $table->string('greeting_company')->nullable();  // "ওয়ালটন" বা "মারসেল"
            $table->text('custom_script')->nullable();       // Custom greeting override
            $table->integer('total_contacts')->default(0);
            $table->integer('called_count')->default(0);
            $table->integer('completed_count')->default(0);
            $table->timestamps();
        });

        // ─── Individual survey call records ────────────────────────────────────
        Schema::create('feedback_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('feedback_campaigns')->cascadeOnDelete();
            $table->string('sr_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('mobile_number');
            $table->string('product_name')->nullable();
            $table->string('district')->nullable();
            $table->date('service_date')->nullable();

            // Call status
            $table->string('call_status')->default('pending'); // pending | calling | completed | no_answer | dropped | callback

            // Q1 — Service received?
            $table->string('service_received')->nullable();    // yes | no | dont_know
            // Q2 — Problem now?
            $table->string('has_problem')->nullable();         // yes | no
            $table->text('problem_details')->nullable();
            // Q3 — Satisfied?
            $table->string('satisfied')->nullable();           // yes | no | dont_know
            $table->text('satisfaction_comment')->nullable();

            // Overall
            $table->text('general_comment')->nullable();       // AI generated summary
            $table->text('call_transcript')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_surveys');
        Schema::dropIfExists('feedback_campaigns');
    }
};
