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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('uri')->nullable()->after('action');
            $table->string('method')->nullable()->after('uri');
            $table->string('route_name')->nullable()->after('method');
            $table->boolean('is_api_route')->default(false)->after('route_name');
            $table->boolean('is_active')->default(true)->after('is_api_route');
            
            // Add indexes for better performance
            $table->index(['resource', 'action', 'is_active']);
            $table->index(['is_api_route', 'is_active']);
            $table->index(['uri', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['resource', 'action', 'is_active']);
            $table->dropIndex(['is_api_route', 'is_active']);
            $table->dropIndex(['uri', 'method']);
            
            $table->dropColumn([
                'uri',
                'method', 
                'route_name',
                'is_api_route',
                'is_active'
            ]);
        });
    }
};
