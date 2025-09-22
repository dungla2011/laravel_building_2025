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
        Schema::create('api_routes', function (Blueprint $table) {
            $table->id();
            $table->string('uri');
            $table->string('method', 10);
            $table->string('resource');
            $table->string('action');
            $table->string('permission_key');
            $table->string('display_name');
            $table->string('route_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure unique combination of URI and method
            $table->unique(['uri', 'method']);
            
            // Index for faster queries
            $table->index(['resource', 'is_active']);
            $table->index('permission_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_routes');
    }
};
