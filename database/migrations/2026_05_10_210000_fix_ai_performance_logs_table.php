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
                $table->string('call_id')->nullable();
                $table->integer('latency_ms')->default(0);
                $table->string('step')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('ai_performance_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_performance_logs', 'latency_ms')) {
                    $table->integer('latency_ms')->default(0)->after('id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_performance_logs');
    }
};
