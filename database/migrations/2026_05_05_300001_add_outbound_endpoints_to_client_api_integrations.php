<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_api_integrations', function (Blueprint $table) {
            // Outbound endpoints — client এর API তে push করার জন্য
            $table->json('outbound_endpoints')->nullable()->after('sync_endpoints');
            // Field mapping — আমাদের field name → client এর field name
            $table->json('field_mapping')->nullable()->after('outbound_endpoints');
        });
    }

    public function down(): void
    {
        Schema::table('client_api_integrations', function (Blueprint $table) {
            $table->dropColumn(['outbound_endpoints', 'field_mapping']);
        });
    }
};
