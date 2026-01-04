<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_price_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_price_id')
                ->constrained('billing_prices')
                ->onDelete('cascade');
            $table->string('tax_behavior')->nullable();
            $table->string('lookup_key')->nullable();
            $table->timestamps();

            $table->unique('billing_price_id');
            $table->unique('lookup_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_price_features');
    }
};
