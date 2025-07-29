<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image'); // Store image path
            $table->string('action_type')->default('url'); // 'url', 'product', 'category', 'page'
            $table->string('action_value')->nullable();    // e.g. URL, slug, ID
            $table->json('extra')->nullable(); // For "params", tracking, campaign_id, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
