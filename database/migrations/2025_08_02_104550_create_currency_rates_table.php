<?php
// database/migrations/xxxx_xx_xx_create_currency_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 12, 6);
            $table->timestamp('last_updated_at');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
            $table->index('last_updated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('currency_rates');
    }
};
