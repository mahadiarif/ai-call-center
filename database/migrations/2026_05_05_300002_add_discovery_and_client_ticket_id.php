<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // client_api_integrations — discovered API schema store করার জন্য
        Schema::table('client_api_integrations', function (Blueprint $table) {
            $table->json('discovered_schema')->nullable()->after('outbound_endpoints');
            $table->timestamp('last_discovered_at')->nullable()->after('discovered_schema');
        });

        // সব ticket table এ client_ticket_id যোগ করো — push করার পরে client এর ID রাখার জন্য
        foreach (['sr_tickets', 'qm_complaints', 'qm_parts_queries', 'qm_bill_queries'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('client_ticket_id')->nullable()->after('id');
                $table->string('client_ticket_status')->nullable()->after('client_ticket_id');
                $table->timestamp('client_synced_at')->nullable()->after('client_ticket_status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_api_integrations', function (Blueprint $table) {
            $table->dropColumn(['discovered_schema', 'last_discovered_at']);
        });
        foreach (['sr_tickets', 'qm_complaints', 'qm_parts_queries', 'qm_bill_queries'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropColumn(['client_ticket_id', 'client_ticket_status', 'client_synced_at']);
            });
        }
    }
};
