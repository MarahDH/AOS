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
        Schema::table('offers', function (Blueprint $table) {
            // Change processing calculation fields to DOUBLE with default values
            $table->double('calculation_processing_lfm_hourly_rate')->nullable()->default(40)->change();
            $table->double('calculation_processing_piece_hourly_rate')->nullable()->default(40)->change();
            $table->double('calculation_processing_lfm_runtime')->nullable()->default(0)->change();
            $table->double('calculation_processing_piece_runtime')->nullable()->default(0)->change();
            $table->integer('calculation_processing_lfm_runtime_factor')->nullable()->default(1)->change();
            $table->integer('calculation_processing_piece_runtime_factor')->nullable()->default(1)->change();
            $table->double('calculation_processing_lfm_packing_time')->nullable()->default(0)->change();
            $table->double('calculation_processing_piece_packing_time')->nullable()->default(0)->change();
            $table->integer('calculation_processing_lfm_packing_time_factor')->nullable()->default(2)->change();
            $table->integer('calculation_processing_piece_packing_time_factor')->nullable()->default(2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Revert back to original field types without defaults
            $table->float('calculation_processing_lfm_hourly_rate')->nullable()->change();
            $table->float('calculation_processing_piece_hourly_rate')->nullable()->change();
            $table->float('calculation_processing_lfm_runtime')->nullable()->change();
            $table->float('calculation_processing_piece_runtime')->nullable()->change();
            $table->integer('calculation_processing_lfm_runtime_factor')->nullable()->change();
            $table->integer('calculation_processing_piece_runtime_factor')->nullable()->change();
            $table->float('calculation_processing_lfm_packing_time')->nullable()->change();
            $table->float('calculation_processing_piece_packing_time')->nullable()->change();
            $table->integer('calculation_processing_lfm_packing_time_factor')->nullable()->change();
            $table->integer('calculation_processing_piece_packing_time_factor')->nullable()->change();
        });
    }
};
