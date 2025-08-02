<?php
// database/migrations/xxxx_xx_xx_add_currency_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('total');
            $table->decimal('exchange_rate', 12, 6)->default(1)->after('currency');
            $table->json('original_amounts')->nullable()->after('exchange_rate'); // Store original amounts in base currency
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'original_amounts']);
        });
    }
};
