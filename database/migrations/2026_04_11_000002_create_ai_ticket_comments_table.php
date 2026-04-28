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
        Schema::create('ai_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_ticket_id');
            $table->string('commenter_name');
            $table->string('commenter_mobile')->nullable();
            $table->longText('comment_text');
            $table->timestamps();

            $table->foreign('ai_ticket_id')->references('id')->on('ai_tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_ticket_comments');
    }
};
