<?php
// database/migrations/xxxx_xx_xx_create_splash_screens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('splash_screens', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_url');
            $table->text('description')->nullable();
            $table->enum('type', ['event', 'promotion', 'announcement', 'seasonal', 'general'])->default('general');
            $table->boolean('is_active')->default(false);
            $table->integer('display_duration')->default(3); // seconds
            $table->integer('sort_order')->default(0);
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->string('target_audience')->nullable(); // all, new_users, returning_users
            $table->json('metadata')->nullable(); // Additional data like colors, animations, etc.
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['start_date', 'end_date']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('splash_screens');
    }
};
