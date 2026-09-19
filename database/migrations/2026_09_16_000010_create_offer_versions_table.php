<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->json('old_data')->nullable();
            $table->json('new_data');
            $table->dateTime('detected_at');
            // crawler / admin / user_report
            $table->string('source');
            $table->timestamps();

            $table->index('offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_versions');
    }
};
