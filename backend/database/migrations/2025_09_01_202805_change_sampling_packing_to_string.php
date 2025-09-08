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
            // Change runningcard_sampling_packing from integer to string
            $table->string('runningcard_sampling_packing', 31)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Revert runningcard_sampling_packing back to integer
            $table->integer('runningcard_sampling_packing')->nullable()->change();
        });
    }
};
