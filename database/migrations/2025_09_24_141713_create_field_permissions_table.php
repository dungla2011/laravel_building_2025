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
        Schema::create('field_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('table_name'); // Tên table (users, posts, etc.)
            $table->string('field_name'); // Tên field (name, email, password, etc.)
            $table->boolean('can_read')->default(false); // Quyền đọc
            $table->boolean('can_write')->default(false); // Quyền ghi
            $table->timestamps();
            
            // Unique constraint: mỗi role chỉ có 1 permission per field per table
            $table->unique(['role_id', 'table_name', 'field_name']);
            
            // Index để query nhanh
            $table->index(['table_name', 'field_name']);
            $table->index(['role_id', 'table_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
    }
};
