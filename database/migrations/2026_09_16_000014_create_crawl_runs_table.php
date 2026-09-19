<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_source_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->integer('http_status')->nullable();
            // ok / http_error / timeout / captcha / blocked / parse_failed / changed_layout / not_found
            $table->string('status');
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('crawl_source_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_runs');
    }
};
