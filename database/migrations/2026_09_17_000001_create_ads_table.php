<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_url')->nullable();
            $table->text('html_snippet')->nullable();
            $table->string('target_url');
            $table->string('position', 32)->default('between_cards');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['position', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
