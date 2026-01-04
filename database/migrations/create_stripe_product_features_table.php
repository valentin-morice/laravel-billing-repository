<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_product_id')
                ->constrained('billing_products')
                ->onDelete('cascade');
            $table->string('tax_code')->nullable();
            $table->string('statement_descriptor')->nullable();
            $table->timestamps();

            $table->unique('billing_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_product_features');
    }
};
