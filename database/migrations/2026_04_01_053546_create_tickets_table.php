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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('phone_number'); // কাস্টমারের নম্বর
        $table->string('subject')->nullable(); // কাস্টমার কী নিয়ে কথা বলেছে
        $table->text('issue_summary')->nullable(); // এআই পুরো কথার একটা সারসংক্ষেপ করবে
        $table->longText('conversation_history')->nullable(); // পুরো কলের টেক্সট (A to Z কথা)
        $table->string('status')->default('resolved_by_ai'); // open, resolved_by_ai, human_needed
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
