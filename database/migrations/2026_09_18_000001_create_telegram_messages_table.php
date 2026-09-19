<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            // Stable identity of the inbound message (telegram message_id)
            $table->string('telegram_message_id')->nullable()->unique();
            // The chat that sent it (user id or channel)
            $table->string('chat_id')->nullable();
            // Who sent it (user name) — optional, used for audit
            $table->string('sender_name')->nullable();
            // Raw payload from telegram webhook (for traceability)
            $table->json('raw_payload')->nullable();
            // The actual body (text) — what we feed to the LLM
            $table->text('text')->nullable();
            // An optional URL that the user attached (from text or separate field)
            $table->string('url')->nullable();
            // status: pending -> processed -> success -> failed -> skipped
            $table->string('status')->default('pending')->index();
            // error message if status = failed
            $table->text('error')->nullable();
            // result reference (to AiAnalysisResult)
            $table->unsignedBigInteger('ai_analysis_result_id')->nullable()->index();
            // created offer reference (to offers)
            $table->unsignedBigInteger('offer_id')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
