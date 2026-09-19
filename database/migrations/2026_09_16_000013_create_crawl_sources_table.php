<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            // website / pricing / models / docs / telegram / github
            $table->string('source_type');
            $table->string('url');
            $table->json('parser_hint')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('crawl_frequency_hours')->default(24);
            $table->timestamps();

            $table->unique(['provider_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_sources');
    }
};
