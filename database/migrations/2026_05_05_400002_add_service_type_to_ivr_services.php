<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            // Service type — SR | QM Complaint | QM Parts | QM Bill | Survey | General
            $table->string('service_type')->default('sr')->after('service_name');
        });
    }

    public function down(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
