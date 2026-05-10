<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sr_tickets', function (Blueprint $table) {
            $table->boolean('is_emergency')->default(false)->after('brand')
                  ->comment('Emergency SR — technician overdue 3+ days');
        });
    }

    public function down(): void
    {
        Schema::table('sr_tickets', function (Blueprint $table) {
            $table->dropColumn('is_emergency');
        });
    }
};
