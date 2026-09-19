<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_run_id')->constrained()->cascadeOnDelete();
            $table->json('extracted_json');
            $table->json('free_models')->nullable();
            $table->unsignedBigInteger('credits_amount')->nullable();
            $table->string('credits_unit')->nullable();
            $table->boolean('requires_card')->nullable();
            $table->boolean('requires_phone')->nullable();
            $table->text('evidence_text')->nullable();
            $table->timestamps();

            $table->index('crawl_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_results');
    }
};
