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
        Schema::create('role_route_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_route_id')->constrained()->onDelete('cascade');
            $table->boolean('can_access')->default(false);
            $table->timestamps();
            
            // Ensure unique combination of role and route
            $table->unique(['role_id', 'api_route_id']);
            
            // Index for faster queries
            $table->index(['role_id', 'can_access']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_route_permissions');
    }
};
