<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->text('global_persona')->nullable()->after('contact_info');
            $table->text('ai_forbidden_topics')->nullable()->after('global_persona');
            $table->text('ai_tone_guidelines')->nullable()->after('ai_forbidden_topics');
            $table->text('ai_special_knowledge')->nullable()->after('ai_tone_guidelines');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['global_persona', 'ai_forbidden_topics', 'ai_tone_guidelines', 'ai_special_knowledge']);
        });
    }
};
