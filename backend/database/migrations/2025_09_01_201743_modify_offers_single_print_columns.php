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
            // Modify single print columns with default values
            $table->double('calculation_additional_single_print')->nullable()->default(1)->change();
            $table->double('calculation_additional_single_print_price')->nullable()->default(0.01)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Revert to original values
            $table->double('calculation_additional_single_print')->nullable()->change();
            $table->double('calculation_additional_single_print_price')->nullable()->change();
        });
    }
};
