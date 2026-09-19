<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_verification_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verification_method_id')->constrained()->cascadeOnDelete();

            $table->unique(['offer_id', 'verification_method_id']);
            $table->index('verification_method_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_verification_method');
    }
};
