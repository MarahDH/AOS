<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default values to existing single print columns
        DB::statement('ALTER TABLE offers MODIFY COLUMN calculation_additional_single_print DOUBLE NULL DEFAULT 1');
        DB::statement('ALTER TABLE offers MODIFY COLUMN calculation_additional_single_print_price DOUBLE NULL DEFAULT 0.01');
        
        // Update existing NULL values to use the new defaults
        DB::statement('UPDATE offers SET calculation_additional_single_print = 1 WHERE calculation_additional_single_print IS NULL');
        DB::statement('UPDATE offers SET calculation_additional_single_print_price = 0.01 WHERE calculation_additional_single_print_price IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove default values and revert to nullable without defaults
        DB::statement('ALTER TABLE offers MODIFY COLUMN calculation_additional_single_print DOUBLE NULL');
        DB::statement('ALTER TABLE offers MODIFY COLUMN calculation_additional_single_print_price DOUBLE NULL');
    }
};
