<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            <<<SQL
            CREATE OR REPLACE VIEW offers_calculated AS 
            SELECT 
                oc.*,

                -- Jahresumsatz = AVG(Marge1-Meterpreis, Marge2-Meterpreis) * Jahresbedarf
                (
                    (oc._pricing_endprices_graduated_without_confection_lfm_quantityA + oc._pricing_endprices_graduated_without_confection_lfm_quantityB)
                    / 2 * oc.calculation_working_annual_requirement_estimated
                ) AS `_pricing_requirement_annual_sales`,

                -- Fixkosten [€] = Jahresumsatz-Rohstoffeinsatz-Zeitkosten
                (
                    (
                        (oc._pricing_endprices_graduated_without_confection_lfm_quantityA + oc._pricing_endprices_graduated_without_confection_lfm_quantityB)
                        / 2 * oc.calculation_working_annual_requirement_estimated
                    ) 
                    - oc._pricing_costs_yearly_raw_material_quantity - oc._pricing_costs_yearly_time_costs_quantity
                ) AS `_pricing_costs_yearly_fixcosts`,

                -- Stück-Längen-Preise Staffel/m -- piece-length-prices graduated lfm
                oc._pricing_endprices_graduated_without_confection_lfm_quantityA AS `_pricing_piece_length_prices_graduated_lfm_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB AS `_pricing_piece_length_prices_graduated_lfm_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC AS `_pricing_piece_length_prices_graduated_lfm_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD AS `_pricing_piece_length_prices_graduated_lfm_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE AS `_pricing_piece_length_prices_graduated_lfm_quantityE`,
               
                -- Stück-Längen-Preise Länge 625mm -- piece-length-prices length 625mm  
                oc._pricing_endprices_graduated_without_confection_lfm_quantityA * oc.pricing_piece_length_prices_length1 / 1000 AS `_pricing_piece_length_prices_length1_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB * oc.pricing_piece_length_prices_length1 / 1000 AS `_pricing_piece_length_prices_length1_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC * oc.pricing_piece_length_prices_length1 / 1000 AS `_pricing_piece_length_prices_length1_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD * oc.pricing_piece_length_prices_length1 / 1000 AS `_pricing_piece_length_prices_length1_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE * oc.pricing_piece_length_prices_length1 / 1000 AS `_pricing_piece_length_prices_length1_quantityE`,

                oc._pricing_endprices_graduated_without_confection_lfm_quantityA * oc.pricing_piece_length_prices_length2 / 1000 AS `_pricing_piece_length_prices_length2_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB * oc.pricing_piece_length_prices_length2 / 1000 AS `_pricing_piece_length_prices_length2_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC * oc.pricing_piece_length_prices_length2 / 1000 AS `_pricing_piece_length_prices_length2_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD * oc.pricing_piece_length_prices_length2 / 1000 AS `_pricing_piece_length_prices_length2_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE * oc.pricing_piece_length_prices_length2 / 1000 AS `_pricing_piece_length_prices_length2_quantityE`,

                oc._pricing_endprices_graduated_without_confection_lfm_quantityA * oc.pricing_piece_length_prices_length3 / 1000 AS `_pricing_piece_length_prices_length3_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB * oc.pricing_piece_length_prices_length3 / 1000 AS `_pricing_piece_length_prices_length3_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC * oc.pricing_piece_length_prices_length3 / 1000 AS `_pricing_piece_length_prices_length3_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD * oc.pricing_piece_length_prices_length3 / 1000 AS `_pricing_piece_length_prices_length3_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE * oc.pricing_piece_length_prices_length3 / 1000 AS `_pricing_piece_length_prices_length3_quantityE`,

                oc._pricing_endprices_graduated_without_confection_lfm_quantityA * oc.pricing_piece_length_prices_length4 / 1000 AS `_pricing_piece_length_prices_length4_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB * oc.pricing_piece_length_prices_length4 / 1000 AS `_pricing_piece_length_prices_length4_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC * oc.pricing_piece_length_prices_length4 / 1000 AS `_pricing_piece_length_prices_length4_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD * oc.pricing_piece_length_prices_length4 / 1000 AS `_pricing_piece_length_prices_length4_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE * oc.pricing_piece_length_prices_length4 / 1000 AS `_pricing_piece_length_prices_length4_quantityE`,

                oc._pricing_endprices_graduated_without_confection_lfm_quantityA * oc.pricing_piece_length_prices_length5 / 1000 AS `_pricing_piece_length_prices_length5_quantityA`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityB * oc.pricing_piece_length_prices_length5 / 1000 AS `_pricing_piece_length_prices_length5_quantityB`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityC * oc.pricing_piece_length_prices_length5 / 1000 AS `_pricing_piece_length_prices_length5_quantityC`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityD * oc.pricing_piece_length_prices_length5 / 1000 AS `_pricing_piece_length_prices_length5_quantityD`,
                oc._pricing_endprices_graduated_without_confection_lfm_quantityE * oc.pricing_piece_length_prices_length5 / 1000 AS `_pricing_piece_length_prices_length5_quantityE`

            FROM offers_calculated_temp3 oc;
            SQL
        );
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS offers_calculated');
    }
};
