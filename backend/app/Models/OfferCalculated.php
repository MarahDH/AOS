<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferCalculated extends Model
{
    protected $table = 'offers_calculated';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'general_created_by_user_id');
    }

    public function status()
    {
        return $this->belongsTo(OfferStatus::class, 'general_status_id');
    }

    /**
     * List of fields that are allowed to be updated individually.
     */
    public static function allowedFields(): array
    {
        $basicFields = [
            // General
            'general_offer_number',
            'general_status_id',
            'general_profile_description',
            'general_color',
            'general_packaging',
            'general_article_number',
            'general_tool_number',
            'general_order_number',
            'general_delivery_type',
            'general_customer',
            'general_customer_contact_person',
            'general_customer_article_number',
            'general_request_number',
            'general_request_date',
            'general_profile_crosssection',
            'general_raw_material_price_total_overwritten',
            'general_raw_material_purchase_discount',
            'general_comments',

            // Calculation Quantities
            'calculation_quantityA',
            'calculation_quantityB',
            'calculation_quantityC',
            'calculation_quantityD',
            'calculation_quantityE',

            // Processing Per Meter
            'calculation_processing_lfm_hourly_rate',
            'calculation_processing_lfm_runtime',
            'calculation_processing_lfm_runtime_factor',
            'calculation_processing_lfm_packing_time',
            'calculation_processing_lfm_packing_time_factor',
            '_calculation_processing_lfm_expense',
            '_calculation_processing_lfm_costs',

            // Processing Per Piece
            'calculation_processing_piece_hourly_rate',
            'calculation_processing_piece_runtime',
            'calculation_processing_piece_runtime_factor',
            'calculation_processing_piece_packing_time',
            'calculation_processing_piece_packing_time_factor',
            '_calculation_processing_piece_expense',
            '_calculation_processing_piece_costs',

            // Additional
            'calculation_additional_setup_time',
            'calculation_additional_hourly_rate',
            'calculation_additional_transport_costs_total',
            'calculation_additional_box_count',
            'calculation_additional_box_price_per_piece',
            'calculation_additional_box_price_flat_additional',
            'calculation_additional_single_print',
            'calculation_additional_single_print_price',
            '_calculation_additional_setup_costs_total',
            '_calculation_additional_setup_costs_lfm',
            '_calculation_additional_transport_costs_lfm',
            '_calculation_additional_box_costs_lfm',
            '_calculation_additional_single_print_lfm',

            // Working
            'calculation_working_setup_quantity_relative',
            'calculation_working_extrusion_speed',
            '_calculation_working_setup_quantity_lfm',
            '_calculation_working_setup_time',
            '_calculation_working_tool_costs_customer_relative',

            'calculation_working_annual_requirement_estimated',
            'calculation_working_tool_costs_total',
            'calculation_working_tool_costs_customer',
            'calculation_working_allocation_costs_additional',
            'calculation_working_tool_costs_amortization_years',
            '_calculation_working_allocation_costs_lfm',
            'calculation_working_profile_cross_section_deviation_lower',
            'calculation_working_profile_cross_section_deviation_upper',
            '_calculation_working_density_total',
            '_calculation_working_profile_weight_lowerborder',
            '_calculation_working_profile_weight_average',
            '_calculation_working_profile_weight_upperborder',
            'calculation_working_setup_quantity_total',
            'calculation_working_hourly_rate',
            'calculation_working_additional_costs',
            'calculation_working_commission',
            'calculation_working_profit',
            'calculation_working_discount',
            // 'calculation_working_setup_quantity_additional',

            // Output/Generated Fields
            'output_per_hour',

            // Pricing Graduated Additions
            'pricing_grad_qtyA_add_hourlyrate',
            'pricing_grad_qtyB_add_hourlyrate',
            'pricing_grad_qtyC_add_hourlyrate',
            'pricing_grad_qtyD_add_hourlyrate',
            'pricing_grad_qtyE_add_hourlyrate',
            'pricing_grad_qtyB_add_setupcosts',
            'pricing_grad_qtyC_add_setupcosts',
            'pricing_grad_qtyD_add_setupcosts',
            'pricing_grad_qtyE_add_setupcosts',
            'pricing_grad_qtyB_add_transport',
            'pricing_grad_qtyC_add_transport',
            'pricing_grad_qtyD_add_transport',
            'pricing_grad_qtyE_add_transport',

            // Static Pricing Fields
            '_pricing_requirement_annual_sales',
            '_pricing_costs_calc_production_time',
            '_pricing_costs_calc_time_costs_quantity',
            '_pricing_costs_yearly_time_costs_quantity',
            '_pricing_costs_calc_raw_material_quantity',
            '_pricing_costs_calc_raw_material_setup_quantity',
            '_pricing_costs_calc_raw_material_quantity_total',
            '_pricing_costs_calc_raw_material_price_total',
            '_pricing_costs_yearly_raw_material_quantity',
            '_pricing_costs_yearly_fixcosts',
            'pricing_costs_calc_price_additional_lfm',
            'pricing_costs_calc_price_additional_lfm_desc',
            'pricing_graduated_calculation_additional_setup_quantity',
            '_pricing_endprices_calc_price_additional_lfm_total',

            // End Prices
            '_pricing_endprices_calc_packing_costs',
            '_pricing_endprices_calc_transport_costs',
            '_pricing_endprices_calc_print_costs',
            '_pricing_endprices_calc_confection_lfm_costs',
            '_pricing_endprices_calc_confection_stk_costs',
            '_pricing_endprices_calc_sum',

            // Graduated without Confection
            '_pricing_endprices_graduated_without_confection_lfm_quantityA',
            '_pricing_endprices_graduated_without_confection_lfm_quantityB',
            '_pricing_endprices_graduated_without_confection_lfm_quantityC',
            '_pricing_endprices_graduated_without_confection_lfm_quantityD',
            '_pricing_endprices_graduated_without_confection_lfm_quantityE',

            '_pricing_endprices_graduated_without_confection_stk_quantityA',
            '_pricing_endprices_graduated_without_confection_stk_quantityB',
            '_pricing_endprices_graduated_without_confection_stk_quantityC',
            '_pricing_endprices_graduated_without_confection_stk_quantityD',
            '_pricing_endprices_graduated_without_confection_stk_quantityE',


            // Stucj
            "_pricing_endprices_graduated_pieces_quantityA",
            "_pricing_endprices_graduated_pieces_quantityB",
            "_pricing_endprices_graduated_pieces_quantityC",
            "_pricing_endprices_graduated_pieces_quantityD",
            "_pricing_endprices_graduated_pieces_quantityE",

            // Staffel / m
            '_pricing_endprices_graduated_with_confection_lfm_quantityA',
            '_pricing_endprices_graduated_with_confection_lfm_quantityB',
            '_pricing_endprices_graduated_with_confection_lfm_quantityC',
            '_pricing_endprices_graduated_with_confection_lfm_quantityD',
            '_pricing_endprices_graduated_with_confection_lfm_quantityE',

            // --  Staffel / stk
            '_pricing_endprices_graduated_with_confection_stk_quantityA',
            '_pricing_endprices_graduated_with_confection_stk_quantityB',
            '_pricing_endprices_graduated_with_confection_stk_quantityC',
            '_pricing_endprices_graduated_with_confection_stk_quantityD',
            '_pricing_endprices_graduated_with_confection_stk_quantityE',

            // --  Staffelpreise Mengen 
            '_pricing_graduated_calculation_quantityA',
            '_pricing_graduated_calculation_quantityB',
            '_pricing_graduated_calculation_quantityC',
            '_pricing_graduated_calculation_quantityD',
            '_pricing_graduated_calculation_quantityE',

            // --  Staffelpreise Stundensatz
            "_pricing_graduated_calculation_hourly_rate_quantityA",
            '_pricing_graduated_calculation_hourly_rate_quantityB',
            '_pricing_graduated_calculation_hourly_rate_quantityC',
            '_pricing_graduated_calculation_hourly_rate_quantityD',
            '_pricing_graduated_calculation_hourly_rate_quantityE',

            //Staffelpreise Zeitkostenanteil


            '_pricing_graduated_calculation_timecosts_relative_quantityA',
            '_pricing_graduated_calculation_timecosts_relative_quantityB',
            '_pricing_graduated_calculation_timecosts_relative_quantityC',
            '_pricing_graduated_calculation_timecosts_relative_quantityD',
            '_pricing_graduated_calculation_timecosts_relative_quantityE',

            //Staffelpreise Produktionszeit
            '_pricing_graduated_calculation_productiontime_quantityA',
            '_pricing_graduated_calculation_productiontime_quantityB',
            '_pricing_graduated_calculation_productiontime_quantityC',
            '_pricing_graduated_calculation_productiontime_quantityD',
            '_pricing_graduated_calculation_productiontime_quantityE',

            // Staffelpreise Rohstoffmengen

            '_pricing_graduated_calculation_rawmaterialquantity_quantityA',
            '_pricing_graduated_calculation_rawmaterialquantity_quantityB',
            '_pricing_graduated_calculation_rawmaterialquantity_quantityC',
            '_pricing_graduated_calculation_rawmaterialquantity_quantityD',
            '_pricing_graduated_calculation_rawmaterialquantity_quantityE',

            //Staffelpreise Zwischensummen
            '_pricing_graduated_calculation_subtotal_quantityA',
            '_pricing_graduated_calculation_subtotal_quantityB',
            '_pricing_graduated_calculation_subtotal_quantityC',
            '_pricing_graduated_calculation_subtotal_quantityD',
            '_pricing_graduated_calculation_subtotal_quantityE',

            // Staffelpreise Zwischensummen / m
            '_pricing_graduated_calculation_subtotal_lfm_quantityA',
            '_pricing_graduated_calculation_subtotal_lfm_quantityB',
            '_pricing_graduated_calculation_subtotal_lfm_quantityC',
            '_pricing_graduated_calculation_subtotal_lfm_quantityD',
            '_pricing_graduated_calculation_subtotal_lfm_quantityE',

            // Maschinenauslastung->Stunden
            '_pricing_machine_utilization_hours_quantity_yearly',
            '_pricing_machine_utilization_hours_quantityA',
            '_pricing_machine_utilization_hours_quantityB',
            '_pricing_machine_utilization_hours_quantityC',
            '_pricing_machine_utilization_hours_quantityD',
            '_pricing_machine_utilization_hours_quantityE',


            // Maschinenauslastung->Tage 
            '_pricing_machine_utilization_days_quantity_yearly',
            '_pricing_machine_utilization_days_quantityA',
            '_pricing_machine_utilization_days_quantityB',
            '_pricing_machine_utilization_days_quantityC',
            '_pricing_machine_utilization_days_quantityD',
            '_pricing_machine_utilization_days_quantityE',

            // Maschinenauslastung->Wochen
            '_pricing_machine_utilization_weeks_quantity_yearly',
            '_pricing_machine_utilization_weeks_quantityA',
            '_pricing_machine_utilization_weeks_quantityB',
            '_pricing_machine_utilization_weeks_quantityC',
            '_pricing_machine_utilization_weeks_quantityD',
            '_pricing_machine_utilization_weeks_quantityE',

            //Maschinenauslastung->Monate
            '_pricing_machine_utilization_months_quantity_yearly',
            '_pricing_machine_utilization_months_quantityA',
            '_pricing_machine_utilization_months_quantityB',
            '_pricing_machine_utilization_months_quantityC',
            '_pricing_machine_utilization_months_quantityD',
            '_pricing_machine_utilization_months_quantityE',

            //Maschinenauslastung->Maschinenauslastung 
            '_pricing_machine_utilization_yearly_relative',

            // Other
            'pricing_machine_utilization_annual_machine_capacity',

            'pricing_piece_length_prices_length1',
            'pricing_piece_length_prices_length2',
            'pricing_piece_length_prices_length3',
            'pricing_piece_length_prices_length4',
            'pricing_piece_length_prices_length5',

            //Stück-Längen-Preise Staffel/m 
            '_pricing_piece_length_prices_graduated_lfm_quantityA',
            '_pricing_piece_length_prices_graduated_lfm_quantityB',
            '_pricing_piece_length_prices_graduated_lfm_quantityC',
            '_pricing_piece_length_prices_graduated_lfm_quantityD',
            '_pricing_piece_length_prices_graduated_lfm_quantityE',

            //Stück-Längen-Preise Länge 625mm / length 1
            '_pricing_piece_length_prices_length1_quantityA',
            '_pricing_piece_length_prices_length1_quantityB',
            '_pricing_piece_length_prices_length1_quantityC',
            '_pricing_piece_length_prices_length1_quantityD',
            '_pricing_piece_length_prices_length1_quantityE',

            //Stück-Längen-Preise Länge 1000mm / length 2
            '_pricing_piece_length_prices_length2_quantityA',
            '_pricing_piece_length_prices_length2_quantityB',
            '_pricing_piece_length_prices_length2_quantityC',
            '_pricing_piece_length_prices_length2_quantityD',
            '_pricing_piece_length_prices_length2_quantityE',

            //Stück-Längen-Preise Länge 1250mm / length 3
            '_pricing_piece_length_prices_length3_quantityA',
            '_pricing_piece_length_prices_length3_quantityB',
            '_pricing_piece_length_prices_length3_quantityC',
            '_pricing_piece_length_prices_length3_quantityD',
            '_pricing_piece_length_prices_length3_quantityE',

            //Stück-Längen-Preise Länge 1333mm / length 4
            '_pricing_piece_length_prices_length4_quantityA',
            '_pricing_piece_length_prices_length4_quantityB',
            '_pricing_piece_length_prices_length4_quantityC',
            '_pricing_piece_length_prices_length4_quantityD',
            '_pricing_piece_length_prices_length4_quantityE',

            //Stück-Längen-Preise Länge 1500mm / length 5
            '_pricing_piece_length_prices_length5_quantityA',
            '_pricing_piece_length_prices_length5_quantityB',
            '_pricing_piece_length_prices_length5_quantityC',
            '_pricing_piece_length_prices_length5_quantityD',
            '_pricing_piece_length_prices_length5_quantityE',



            'runningcard_sampling_date',
            'runningcard_sampling_quantity',
            'runningcard_sampling_length',
            'runningcard_sampling_packing',
            'runningcard_sampling_indication',
            'runningcard_qualitity_indication',
            'runningcard_printing',
            'runningcard_packing_type',
            'runningcard_packing_variant',
            'runningcard_packing_length',
            'runningcard_packing_packing_unit',
            'runningcard_packing_quantity',
            'runningcard_packing_description',

            'runningcard_hourlyrecording_construction',
            'runningcard_hourlyrecording_toolwork',
            'runningcard_hourlyrecording_entry',
            'runningcard_hourlyrecording_entrystitches',
            'runningcard_hourlyrecording_entrydriver_user_id',
            'runningcard_hourlyrecording_toolmaker_user_id',

            'runningcard_profile_weight_IST',
            'runningcard_extrusion_speed_IST',

            'pricing_piece_length_prices_length1',
            'pricing_piece_length_prices_length2',
            'pricing_piece_length_prices_length3',
            'pricing_piece_length_prices_length4',
            'pricing_piece_length_prices_length5',

            'runningcard_tool_costs',
            'runningcard_tool_cost_type',
            'runningcard_tool_hint',
            "_runningcard_hourlyrecording_total",

            //
            "_calculation_working_commission_total",
            "_calculation_working_profit_total",
            "_calculation_working_discount_total",
            ///Zwischensumme1: Zeitkosten + Rohstoffpreis (Kalkmenge)
            "_zwischensumme1",
            "_zwischensumme2",
            "_zwischensumme2_meterpreis",
            "_zwischensumme3_meterpreis",
            "_calculation_working_commission_total_lfm",
            "_calculation_working_commission_total",
            "_calculation_working_profit_total_lfm",
            "_calculation_working_discount_total_lfm",
            "_calculation_working_discount_profit_commssion_total_lfm",
            "_calculation_working_discount_profit_commssion_total",
            "_pricing_endprices_calc_sum_lfm",
            "_pricing_endprices_calc_sum",

            "_pricing_graduated_calculation_subtotal2_quantityA",
            "_pricing_graduated_calculation_subtotal2_quantityB",
            "_pricing_graduated_calculation_subtotal2_quantityC",
            "_pricing_graduated_calculation_subtotal2_quantityD",
            "_pricing_graduated_calculation_subtotal2_quantityE",
        ];

        return array_merge(
            $basicFields,
            RawMaterial::rawMaterialAllowedFields()
        );
    }
}
