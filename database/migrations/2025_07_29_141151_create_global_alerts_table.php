<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('global_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'maintenance', 'update', etc.
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('buttons')->nullable(); // For links/actions
            $table->string('status')->default('inactive'); // 'active', 'inactive'
            $table->json('metadata')->nullable(); // (optional, for future expandability)
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('global_alerts');
    }
};
