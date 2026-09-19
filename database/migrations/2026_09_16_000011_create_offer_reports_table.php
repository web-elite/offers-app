<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            // expired / fake / broken_link / not_free_anymore / wrong_info / unexpected_verification / other
            $table->string('report_type');
            $table->text('details_fa')->nullable();
            // pending / resolved / rejected
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('offer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_reports');
    }
};
