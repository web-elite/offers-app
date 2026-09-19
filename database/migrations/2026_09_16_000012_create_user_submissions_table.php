<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name');
            $table->string('url');
            $table->text('offer_note_fa')->nullable();
            $table->string('contact')->nullable();
            // pending / approved / rejected
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_submissions');
    }
};
