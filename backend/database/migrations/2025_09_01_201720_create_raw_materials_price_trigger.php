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
        // Create trigger to automatically fill price and price_date from raw_materials
        // when inserting into offers_raw_materials
        DB::unprepared('
            CREATE TRIGGER trg_offers_rm_fill_price_BI
            BEFORE INSERT ON offers_raw_materials
            FOR EACH ROW
            BEGIN
              DECLARE v_price DOUBLE;
              DECLARE v_price_date DATE;

              -- Get matching price from raw_materials
              SELECT rm.price, rm.price_date
                INTO v_price, v_price_date
              FROM raw_materials rm
              WHERE rm.id = NEW.raw_material_id
              LIMIT 1;

              -- Set values in the new row
              SET NEW.price = v_price;
              SET NEW.price_date = v_price_date;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS trg_offers_rm_fill_price_BI');
    }
};
