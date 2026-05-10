<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // QM Ticket type: SR = সাধারণ Service Request, QM = Quality Management Ticket
            if (!Schema::hasColumn('service_requests', 'ticket_type')) {
                $table->enum('ticket_type', ['SR', 'QM_COMPLAINT', 'QM_PARTS', 'QM_BILL'])
                      ->default('SR')
                      ->after('ivr_service_id')
                      ->comment('SR=সাধারণ সার্ভিস রিকোয়েস্ট | QM_COMPLAINT=অভিযোগ | QM_PARTS=পার্টস Query | QM_BILL=বিল Query');
            }

            // QM reference number: QM-1234 format এ display করার জন্য
            if (!Schema::hasColumn('service_requests', 'qm_number')) {
                $table->string('qm_number')->nullable()->after('ticket_type')
                      ->comment('QM-XXXX format এ auto-generate হবে');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'ticket_type')) {
                $table->dropColumn('ticket_type');
            }
            if (Schema::hasColumn('service_requests', 'qm_number')) {
                $table->dropColumn('qm_number');
            }
        });
    }
};
