<?php
// FILE: database/migrations/create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->enum('stock_status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->boolean('featured')->default(false);
            $table->json('attributes')->nullable();
            $table->json('meta_data')->nullable();
            $table->date('published_at');
            $table->timestamps();

            $table->index(['status', 'featured']);
            $table->index(['stock_status', 'in_stock']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
