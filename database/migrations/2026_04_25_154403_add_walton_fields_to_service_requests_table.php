<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // service_center already exists, only add brand and comments
            if (!Schema::hasColumn('service_requests', 'brand')) {
                $table->string('brand')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('service_requests', 'comments')) {
                $table->text('comments')->nullable()->after('problem_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'brand')) {
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('service_requests', 'comments')) {
                $table->dropColumn('comments');
            }
        });
    }
};
