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
            // Change general_request_number from integer to string to support alphanumeric values
            $table->string('general_request_number', 63)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Revert back to integer (note: this may cause data loss if non-numeric values exist)
            $table->integer('general_request_number')->nullable()->change();
        });
    }
};

