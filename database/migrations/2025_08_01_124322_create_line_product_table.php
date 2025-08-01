<?php
// database/migrations/xxxx_xx_xx_create_line_product_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('line_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['line_id', 'product_id']);
            $table->index(['line_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_product');
    }
};
