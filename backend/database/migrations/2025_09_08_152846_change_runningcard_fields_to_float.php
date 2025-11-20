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
            $table->float('runningcard_extrusion_speed_IST')->nullable()->change();
            $table->float('runningcard_profile_weight_IST')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->integer('runningcard_extrusion_speed_IST')->nullable()->change();
            $table->integer('runningcard_profile_weight_IST')->nullable()->change();
        });
    }
};
