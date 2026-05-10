<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // যদি টেবিল না থাকে তবে তৈরি করো, আর থাকলে কলাম যোগ করো
        if (!Schema::hasTable('ai_performance_logs')) {
            Schema::create('ai_performance_logs', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable();
                $table->integer('latency_ms')->default(0);
                $table->string('provider')->nullable();
                $table->string('status')->nullable();
                $table->string('step')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('ai_performance_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_performance_logs', 'session_id')) {
                    $table->string('session_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('ai_performance_logs', 'latency_ms')) {
                    $table->integer('latency_ms')->default(0)->after('session_id');
                }
                if (!Schema::hasColumn('ai_performance_logs', 'provider')) {
                    $table->string('provider')->nullable()->after('latency_ms');
                }
                if (!Schema::hasColumn('ai_performance_logs', 'status')) {
                    $table->string('status')->nullable()->after('provider');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_performance_logs');
    }
};
