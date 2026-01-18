<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('billing_products')->onDelete('cascade');
            $table->string('key');
            $table->string('provider_id')->unique();
            $table->integer('amount');
            $table->string('currency', 3);
            $table->json('recurring')->nullable();
            $table->string('nickname')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('trial_period_days')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
            $table->index(['product_id', 'key', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_prices');
    }
};
