<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    if (Schema::hasTable('ivr_fields')) {
        Schema::table('ivr_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('ivr_fields', 'validation_format')) {
                $table->text('validation_format')->nullable()->after('tips');
            }
            if (!Schema::hasColumn('ivr_fields', 'error_message')) {
                $table->text('error_message')->nullable()->after('validation_format');
            }
            if (!Schema::hasColumn('ivr_fields', 'convincing_logic')) {
                $table->text('convincing_logic')->nullable()->after('error_message');
            }
        });
    }
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ivr_fields', function (Blueprint $table) {
            //
        });
    }
};
