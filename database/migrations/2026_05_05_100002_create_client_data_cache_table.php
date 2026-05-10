<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_data_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained('client_api_integrations')->cascadeOnDelete();
            // data_type: customer|product|service_center|sr_history|technician|district|other
            $table->string('data_type')->index();
            $table->string('external_id')->nullable()->index(); // client's record ID
            $table->string('search_key')->nullable()->index();  // phone/email for quick lookup
            $table->string('search_key2')->nullable()->index(); // secondary lookup (e.g., barcode)
            $table->json('data');                               // full JSON record from client
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();        // null = no expiry
            $table->timestamps();

            $table->unique(['integration_id', 'data_type', 'external_id'], 'unique_client_record');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_data_cache');
    }
};
