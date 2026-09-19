<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title_fa');
            $table->text('description_fa')->nullable();
            // forever_free / daily_reset / monthly_credit / signup_bonus / referral_bonus / trial / free_models / free_credits / unknown
            $table->string('free_tier_type')->default('unknown');
            // active / expired / temporarily_unavailable / requires_verification / manual_review / unknown
            $table->string('status')->default('active');
            // free / freemium / trial / pay_as_you_go / subscription
            $table->string('pricing_type')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->unsignedBigInteger('credits_amount')->nullable();
            // usd / credit / token / model / domain
            $table->string('credits_unit')->nullable();
            $table->text('raw_note_fa')->nullable();
            // array of api / web / chat_ui / ide / telegram_bot / unknown
            $table->json('access_types')->nullable();
            $table->dateTime('last_verified_at')->nullable();
            $table->dateTime('last_crawl_at')->nullable();
            $table->dateTime('next_crawl_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index('provider_id');
            $table->index('free_tier_type');
            $table->index('status');
            $table->index('last_verified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
