<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('stripe_products')->onDelete('cascade');
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->integer('amount');
            $table->string('currency', 3);
            $table->json('recurring')->nullable();
            $table->string('nickname')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('stripe_id');
            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_prices');
    }
};
