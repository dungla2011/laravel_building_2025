<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Original filename
            $table->string('file_name'); // Stored filename (with hash)
            $table->string('mime_type'); // image/jpeg, application/pdf, etc.
            $table->string('extension', 10); // jpg, png, pdf, etc.
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('disk', 50)->default('public'); // Storage disk
            $table->string('path'); // Storage path
            $table->string('url')->nullable(); // Public URL
            $table->string('alt_text')->nullable(); // Alt text for images
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, etc.)
            $table->unsignedBigInteger('user_id')->nullable(); // Who uploaded
            $table->string('collection_name')->nullable(); // For grouping (avatar, gallery, etc.)
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            $table->index(['mime_type']);
            $table->index(['user_id']);
            $table->index(['collection_name']);
            $table->index(['is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
