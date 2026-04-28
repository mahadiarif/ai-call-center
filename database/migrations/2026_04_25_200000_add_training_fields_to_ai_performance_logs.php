<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_performance_logs', function (Blueprint $table) {
            $table->enum('training_status', ['suggested', 'confirmed', 'rejected', 'pending'])
                  ->default('pending')
                  ->after('score')
                  ->comment('AI training এর জন্য status');
            
            $table->text('training_notes')->nullable()->after('training_status')
                  ->comment('Admin notes for training');
            
            $table->text('ai_suggestion')->nullable()->after('training_notes')
                  ->comment('AI এর suggestion কেন এটা good/bad');
        });
    }

    public function down(): void
    {
        Schema::table('ai_performance_logs', function (Blueprint $table) {
            $table->dropColumn(['training_status', 'training_notes', 'ai_suggestion']);
        });
    }
};
