<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            // Add voice gender and speed settings if not already present
            if (!Schema::hasColumn('ivr_services', 'voice_gender')) {
                $table->enum('voice_gender', ['Charon', 'Radha'])->default('Charon')->comment('Male (Charon) or Female (Radha) voice');
            }
            if (!Schema::hasColumn('ivr_services', 'voice_speed')) {
                $table->decimal('voice_speed', 3, 2)->default(1.0)->comment('Voice speed: 0.25 to 2.0 (1.0 = normal)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            if (Schema::hasColumn('ivr_services', 'voice_gender')) {
                $table->dropColumn('voice_gender');
            }
            if (Schema::hasColumn('ivr_services', 'voice_speed')) {
                $table->dropColumn('voice_speed');
            }
        });
    }
};
