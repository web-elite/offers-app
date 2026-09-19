<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->text('crawler_value')->nullable();
            $table->text('override_value');
            $table->text('reason');
            $table->string('actor')->nullable();
            $table->timestamps();

            $table->index('offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_overrides');
    }
};
