<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();

            $table->unique(['offer_id', 'ai_model_id']);
            $table->index('ai_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_offer');
    }
};
