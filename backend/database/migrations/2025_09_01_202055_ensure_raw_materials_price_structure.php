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
        // Ensure raw_materials table has the proper price structure for the trigger
        // This migration ensures compatibility with the price sync trigger
        
        Schema::table('raw_materials', function (Blueprint $table) {
            // Ensure price columns exist and have proper types
            if (!Schema::hasColumn('raw_materials', 'price')) {
                $table->float('price')->nullable();
            }
            if (!Schema::hasColumn('raw_materials', 'price_date')) {
                $table->date('price_date')->nullable();
            }
        });
        
        // Ensure offers_raw_materials table has the proper price structure
        Schema::table('offers_raw_materials', function (Blueprint $table) {
            // Ensure price columns exist and have proper types
            if (!Schema::hasColumn('offers_raw_materials', 'price')) {
                $table->float('price')->nullable();
            }
            if (!Schema::hasColumn('offers_raw_materials', 'price_date')) {
                $table->date('price_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed as we're only ensuring structure
        // Columns won't be dropped if they already existed
    }
};
