<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_results', function (Blueprint $table) {
            $table->id();
            // The telegram message that produced this analysis
            $table->unsignedBigInteger('telegram_message_id')->nullable()->index();
            // LLM metadata
            $table->string('model')->nullable();
            // The full LLM response (JSON string)
            $table->json('response')->nullable();
            // Structured fields — flat column copies of the main fields
            $table->string('provider_name')->nullable();
            $table->string('provider_slug')->nullable();
            $table->string('provider_url')->nullable();
            $table->text('offer_title')->nullable();
            $table->text('offer_description')->nullable();
            // Which free tier type was detected (Offer::FREE_TIER_TYPES)
            $table->string('free_tier_type')->nullable();
            // Which access types were detected (Offer::ACCESS_TYPES as JSON)
            $table->json('access_types')->nullable();
            // Credits / price if detectable
            $table->unsignedBigInteger('credits_amount')->nullable();
            $table->string('credits_unit')->nullable();
            $table->string('pricing_type')->nullable();
            // The slug we will use for the offer (unique per-offer)
            $table->string('offer_slug')->nullable();
            // The status of the resulting offer (e.g. 'active')
            $table->string('offer_status')->default('active');
            // Whether this result was actually materialized into an Offer
            $table->boolean('materialized')->default(false);
            // Error message if LLM failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['materialized', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_results');
    }
};
