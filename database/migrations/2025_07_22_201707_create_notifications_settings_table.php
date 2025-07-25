<?php
// FILE: database/migrations/create_notification_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // email, push, sms
            $table->string('category'); // order_status, offers, promotions, etc.
            $table->boolean('enabled')->default(true);
            $table->json('preferences')->nullable(); // Additional preferences
            $table->timestamps();

            $table->unique(['user_id', 'type', 'category']);
            $table->index(['user_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
