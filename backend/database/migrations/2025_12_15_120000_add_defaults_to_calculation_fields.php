<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing NULL values to defaults
        DB::table('offers')
            ->whereNull('calculation_additional_setup_time')
            ->update(['calculation_additional_setup_time' => 3]);
        
        DB::table('offers')
            ->whereNull('calculation_additional_hourly_rate')
            ->update(['calculation_additional_hourly_rate' => 42]);
        
        DB::table('offers')
            ->whereNull('calculation_working_setup_quantity_relative')
            ->update(['calculation_working_setup_quantity_relative' => 15]);
        
        DB::table('offers')
            ->whereNull('calculation_working_hourly_rate')
            ->update(['calculation_working_hourly_rate' => 60]);

        // Add default values for calculation fields
        Schema::table('offers', function (Blueprint $table) {
            $table->integer('calculation_additional_setup_time')->nullable()->default(3)->change();
            $table->float('calculation_additional_hourly_rate')->nullable()->default(42)->change();
            $table->integer('calculation_working_setup_quantity_relative')->nullable()->default(15)->change();
            $table->integer('calculation_working_hourly_rate')->nullable()->default(60)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Remove default values
            $table->integer('calculation_additional_setup_time')->nullable()->change();
            $table->float('calculation_additional_hourly_rate')->nullable()->change();
            $table->integer('calculation_working_setup_quantity_relative')->nullable()->change();
            $table->integer('calculation_working_hourly_rate')->nullable()->change();
        });
    }
};

