<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            // 🎙️ Voice Gender - সব voices যোগ করা
            if (Schema::hasColumn('ivr_services', 'voice_gender')) {
                // Existing enum modify করা - সব Gemini Live voices
                DB::statement("ALTER TABLE ivr_services MODIFY voice_gender ENUM('Charon', 'Radha', 'Aoede', 'Kore', 'Puck', 'Ornus', 'Fenrir') DEFAULT 'Charon'");
            }
            
            // 🚨 Escalation fields যোগ করা (যদি না থাকে)
            if (!Schema::hasColumn('ivr_services', 'escalation_agent_number')) {
                $table->string('escalation_agent_number')->nullable()->after('voice_speed');
            }
            
            if (!Schema::hasColumn('ivr_services', 'escalation_trigger')) {
                $table->enum('escalation_trigger', ['any', 'angry', 'repeated', 'requested', 'frustrated'])->default('any')->after('escalation_agent_number');
            }
            
            if (!Schema::hasColumn('ivr_services', 'escalation_calm_script')) {
                $table->text('escalation_calm_script')->nullable()->after('escalation_trigger');
            }
            
            if (!Schema::hasColumn('ivr_services', 'escalation_hold_script')) {
                $table->text('escalation_hold_script')->nullable()->after('escalation_calm_script');
            }
            
            if (!Schema::hasColumn('ivr_services', 'escalation_instructions')) {
                $table->text('escalation_instructions')->nullable()->after('escalation_hold_script');
            }
            
            if (!Schema::hasColumn('ivr_services', 'escalation_collect_before_transfer')) {
                $table->boolean('escalation_collect_before_transfer')->default(true)->after('escalation_instructions');
            }
            
            // Secondary Option fields
            if (!Schema::hasColumn('ivr_services', 'secondary_option_enabled')) {
                $table->boolean('secondary_option_enabled')->default(false)->after('escalation_collect_before_transfer');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_agent_number')) {
                $table->string('secondary_agent_number')->nullable()->after('secondary_option_enabled');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_convince_attempts')) {
                $table->integer('secondary_convince_attempts')->default(2)->after('secondary_agent_number');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_forward_script')) {
                $table->text('secondary_forward_script')->nullable()->after('secondary_convince_attempts');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_collect_name_mobile')) {
                $table->boolean('secondary_collect_name_mobile')->default(true)->after('secondary_forward_script');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_ai_instructions')) {
                $table->text('secondary_ai_instructions')->nullable()->after('secondary_collect_name_mobile');
            }
            
            if (!Schema::hasColumn('ivr_services', 'secondary_convince_scripts')) {
                $table->json('secondary_convince_scripts')->nullable()->after('secondary_ai_instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ivr_services', function (Blueprint $table) {
            // Voice gender revert (শুধু Charon, Radha)
            if (Schema::hasColumn('ivr_services', 'voice_gender')) {
                DB::statement("ALTER TABLE ivr_services MODIFY voice_gender ENUM('Charon', 'Radha') DEFAULT 'Charon'");
            }
            
            // Escalation columns drop
            $columns = [
                'escalation_agent_number',
                'escalation_trigger',
                'escalation_calm_script',
                'escalation_hold_script',
                'escalation_instructions',
                'escalation_collect_before_transfer',
                'secondary_option_enabled',
                'secondary_agent_number',
                'secondary_convince_attempts',
                'secondary_forward_script',
                'secondary_collect_name_mobile',
                'secondary_ai_instructions',
                'secondary_convince_scripts',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('ivr_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
