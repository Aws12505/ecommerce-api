<?php
// FILE: database/migrations/create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // snapshot at time of order
            $table->string('product_sku'); // snapshot at time of order
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // price at time of order
            $table->decimal('total', 10, 2);
            $table->json('product_options')->nullable(); // size, color, etc.
            $table->json('product_snapshot')->nullable(); // full product data at time of order
            $table->timestamps();

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
