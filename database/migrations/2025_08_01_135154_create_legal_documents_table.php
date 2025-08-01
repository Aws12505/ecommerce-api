<?php
// database/migrations/xxxx_xx_xx_create_legal_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['terms_of_service', 'privacy_policy'])->unique();
            $table->string('title');
            $table->longText('content'); // Rich text content (HTML)
            $table->text('plain_content')->nullable(); // Plain text version for search/backup
            $table->string('version')->default('1.0');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['type', 'is_published']);
            $table->index('published_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('legal_documents');
    }
};
