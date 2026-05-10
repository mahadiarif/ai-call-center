<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_api_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->string('name');                        // e.g., "Walton ERP"
            $table->text('description')->nullable();
            $table->string('api_base_url');                // e.g., "https://api.walton.com.bd"
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('auth_type')->default('api_key'); // api_key|bearer|basic|none
            $table->string('auth_header_name')->nullable();  // e.g., "X-API-Key"
            $table->json('sync_endpoints')->nullable();      // array of endpoint configs
            $table->string('webhook_secret')->nullable();    // for push notifications
            $table->integer('sync_interval_minutes')->default(60);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('idle'); // idle|running|success|error
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_api_integrations');
    }
};
