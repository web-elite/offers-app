<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            // offer_view / outbound_click / qr_scan / search / filter_use
            $table->string('event_type');
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
