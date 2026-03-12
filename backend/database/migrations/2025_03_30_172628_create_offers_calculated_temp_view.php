<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateOffersCalculatedTempView extends Migration
{
  public function up(): void
  {
    DB::statement(
      <<<SQL
        CREATE OR REPLACE VIEW offers_calculated_temp AS 
              SELECT 
          o.*, 

          CASE
            WHEN o.`calculation_working_tool_costs_total` = o.`calculation_working_tool_costs_customer` THEN 'Vollkosten'
            ELSE 'anteilig'
          END AS `runningcard_tool_cost_type`,

          (
            o.`calculation_processing_lfm_runtime` * o.`calculation_processing_lfm_runtime_factor` + 
            o.`calculation_processing_lfm_packing_time` * o.`calculation_processing_lfm_packing_time_factor` 
          ) AS `_calculation_processing_lfm_expense`,
          (
            (o.`calculation_processing_lfm_runtime` * o.`calculation_processing_lfm_runtime_factor` + 
            o.`calculation_processing_lfm_packing_time` * o.`calculation_processing_lfm_packing_time_factor`) *
            o.`calculation_processing_lfm_hourly_rate` / 3600 
          ) AS `_calculation_processing_lfm_costs`,


          (
            o.`calculation_processing_piece_runtime` * o.`calculation_processing_piece_runtime_factor` + 
            o.`calculation_processing_piece_packing_time` * o.`calculation_processing_piece_packing_time_factor` 
          ) AS `_calculation_processing_piece_expense`,
          (
            (o.`calculation_processing_piece_runtime` * o.`calculation_processing_piece_runtime_factor` + 
            o.`calculation_processing_piece_packing_time` * o.`calculation_processing_piece_packing_time_factor`) *
            o.`calculation_processing_piece_hourly_rate` / 3600 
          ) AS `_calculation_processing_piece_costs`,


          (
            o.`calculation_additional_setup_time` * o.`calculation_additional_hourly_rate`
          ) AS `_calculation_additional_setup_costs_total`,
          (
            o.`calculation_additional_setup_time` * o.`calculation_additional_hourly_rate` / 
            o.`calculation_quantityA`
          ) AS `_calculation_additional_setup_costs_lfm`,
          (
            o.`calculation_additional_transport_costs_total` / o.`calculation_quantityA`
          ) AS `_calculation_additional_transport_costs_lfm`,
          (
            ((o.`calculation_additional_box_count` * o.`calculation_additional_box_price_per_piece`) + 
            o.`calculation_additional_box_price_flat_additional`) / o.`calculation_quantityA`
          ) AS `_calculation_additional_box_costs_lfm`,
          (
            (o.`calculation_additional_single_print` * o.`calculation_additional_single_print_price`)
          ) AS `_calculation_additional_single_print_lfm`,


          (
            o.`calculation_working_setup_quantity_relative` * o.`calculation_quantityA` / 100 
          ) AS `_calculation_working_setup_quantity_lfm`,
          (
            (o.`calculation_working_setup_quantity_relative` * o.`calculation_quantityA` / 100) / 
            o.`calculation_working_extrusion_speed` 
          ) AS `_calculation_working_setup_time`,
          (
            o.`calculation_working_tool_costs_customer` / o.`calculation_working_tool_costs_total` * 100 
          ) AS `_calculation_working_tool_costs_customer_relative`,
          (
            
              (o.`calculation_working_allocation_costs_additional` +
              o.`calculation_working_tool_costs_total` -
              o.`calculation_working_tool_costs_customer`) /
              (o.`calculation_working_annual_requirement_estimated` * 
              o.`calculation_working_tool_costs_amortization_years`)
              
          ) AS `_calculation_working_allocation_costs_lfm`,

      -- raw_material average density over the raw_materials pultiplied by their share
      -- it has to be sumed up, because of the multiplication with the share. So its not directly the average.
          (
            SELECT SUM(r.density * o_r.share/100)
            FROM offers_raw_materials o_r 
            JOIN raw_materials r ON (r.id=o_r.raw_material_id)
            WHERE (o.id=o_r.offer_id)
          ) AS `_calculation_working_density_total`, 
          
      -- _calculation_working_density_total * o.general_profile_crosssection * 
      -- (100 - o.calculation_working_profile_cross_section_deviation_lower)/100
          (
            (
            SELECT SUM(r.density * o_r.share/100)
            FROM offers_raw_materials o_r 
            JOIN raw_materials r ON (r.id=o_r.raw_material_id)
            WHERE (o.id=o_r.offer_id)
          ) 
            * o.general_profile_crosssection 
            * (100 - o.calculation_working_profile_cross_section_deviation_lower)/100
          ) AS `_calculation_working_profile_weight_lowerborder`,

      -- _calculation_working_density_total * general_profile_crosssection      1,9625 + 0,9
          ((
            SELECT SUM(r.density * o_r.share/100)
            FROM offers_raw_materials o_r 
            JOIN raw_materials r ON (r.id=o_r.raw_material_id)
            WHERE (o.id=o_r.offer_id)
          )  * general_profile_crosssection ) AS `_calculation_working_profile_weight_average`,


      -- _calculation_working_density_total * o.general_profile_crosssection * 
      -- (100 + o.calculation_working_profile_cross_section_deviation_upper)/100
          (
            (
            SELECT SUM(r.density * o_r.share/100)
            FROM offers_raw_materials o_r 
            JOIN raw_materials r ON (r.id=o_r.raw_material_id)
            WHERE (o.id=o_r.offer_id)
          ) 
            * o.general_profile_crosssection 
            * (100 + o.calculation_working_profile_cross_section_deviation_upper)/100
          ) AS `_calculation_working_profile_weight_upperborder`,

      -- , zus.Einstellmenge (for Kalk-Menge) / o.calculation_working_extrusion_speed /  60 
          ( ROUND(
            (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / o. `calculation_working_extrusion_speed` / 60 
          , 2)) AS `_pricing_costs_calc_production_time`,

      -- , `_pricing_costs_calc_production_time` * o.calculation_working_hourly_rate
          ( 
            ROUND(
            (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / o. `calculation_working_extrusion_speed` / 60 
            
            * o.calculation_working_hourly_rate
          , 2)) AS `_pricing_costs_calc_time_costs_quantity`,

      -- Zeitkosten gesamt / MengeA(zzgl.Einstellmenge)*Jahresbedarf
      -- _pricing_costs_calc_time_costs_quantity / _pricing_graduated_calculation_quantityA * calculation_working_annual_requirement_estimated
          ( ROUND(
            (
              (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
              / o.`calculation_working_extrusion_speed` / 60         
              * o.calculation_working_hourly_rate
            )   
          / (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
          * (o.`calculation_working_annual_requirement_estimated`)

          , 2)) AS `_pricing_costs_yearly_time_costs_quantity`,

      -- `_calculation_working_profile_weight_average` / 1000 * MengeA(zzgl.Einstellmenge)
          ( ROUND(
              (
                (
                  SELECT SUM(r.density * o_r.share/100)
                  FROM offers_raw_materials o_r 
                  JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                  WHERE (o.id=o_r.offer_id)
                )  * o.general_profile_crosssection 
              ) 
              / (1000 / (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100))
          , 2)) AS `_pricing_costs_calc_raw_material_quantity`,

      -- Einstellmenge [kg] = Rohstoffmenge [kg] * Einstellmenge [%]
      --       = _pricing_costs_calc_raw_material_quantity * calculation_working_setup_quantity_relative
          (
            ROUND(
            (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
              / 1000
              * 
              (
                (
                  SELECT SUM(r.density * o_r.share/100)
                  FROM offers_raw_materials o_r 
                  JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                  WHERE (o.id=o_r.offer_id)
                )  * general_profile_crosssection 
              ) 
            * calculation_working_setup_quantity_relative
            /100
          , 2)) AS `_pricing_costs_calc_raw_material_setup_quantity`,

      -- 	Einstellmenge [kg] + Rohstoffmenge [kg]
      --  _pricing_costs_calc_raw_material_quantity + _pricing_costs_calc_raw_material_setup_quantity
          (
            ROUND(
            (
              (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
              / 1000
              * 
              (
                (
                  SELECT SUM(r.density * o_r.share/100)
                  FROM offers_raw_materials o_r 
                  JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                  WHERE (o.id=o_r.offer_id)
                )  * general_profile_crosssection 
              )
            )
            +
            (  
              (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
              / 1000
              * 
              (
                (
                  SELECT SUM(r.density * o_r.share/100)
                  FROM offers_raw_materials o_r 
                  JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                  WHERE (o.id=o_r.offer_id)
                )  * general_profile_crosssection 
              ) 
              * calculation_working_setup_quantity_relative
              /100
            )
          , 2)) AS `_pricing_costs_calc_raw_material_quantity_total`,

      --  Rohstoffpreis gesamt [€] = "Rohstoffpreis / kg" * "Rohstoffmenge ges"
      --  general_raw_material_price_total_overwritten * _pricing_costs_calc_raw_material_quantity_total
          ( 
            (
              (
                (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
                / 1000
                * 
                (
                  (
                    SELECT SUM(r.density * o_r.share/100)
                    FROM offers_raw_materials o_r 
                    JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                    WHERE (o.id=o_r.offer_id)
                  )  * general_profile_crosssection 
                )
              )
              +
              (  
                (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
                / 1000
                * 
                (
                  (
                    SELECT SUM(r.density * o_r.share/100)
                    FROM offers_raw_materials o_r 
                    JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                    WHERE (o.id=o_r.offer_id)
                  )  * general_profile_crosssection 
                ) 
                * calculation_working_setup_quantity_relative
                /100
              )
            ) * o.`general_raw_material_price_total_overwritten`
          ) AS `_pricing_costs_calc_raw_material_price_total`,

      --  Rohstoffeinsatz [€]    
      -- Rohstoffpreis/m = ProfilgewichtAverage * general_raw_material_price_total_overwritten / 1000
      -- _pricing_costs_yearly_raw_material_quantity = Rohstoffpreis/m * calculation_working_annual_requirement_estimated
      -- => _pricing_costs_calc_raw_material_price_total / _pricing_graduated_calculation_quantityA * calculation_working_annual_requirement_estimated
      --    ( 
      --      (
      --        ((
      --          SELECT SUM(r.density * o_r.share/100)
      --          FROM offers_raw_materials o_r 
      --          JOIN raw_materials r ON (r.id=o_r.raw_material_id)
      --          WHERE (o.id=o_r.offer_id)
      --        )  * general_profile_crosssection )
      --        *
      --        o.`general_raw_material_price_total_overwritten`
      --        / 1000
      --      )
      --      * o.`calculation_working_annual_requirement_estimated`
      --    ) 
          ( 
            (
              (
                (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
                / 1000
                * 
                (
                  (
                    SELECT SUM(r.density * o_r.share/100)
                    FROM offers_raw_materials o_r 
                    JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                    WHERE (o.id=o_r.offer_id)
                  )  * general_profile_crosssection 
                )
              )
              +
              (  
                (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100)
                / 1000
                * 
                (
                  (
                    SELECT SUM(r.density * o_r.share/100)
                    FROM offers_raw_materials o_r 
                    JOIN raw_materials r ON (r.id=o_r.raw_material_id)
                    WHERE (o.id=o_r.offer_id)
                  )  * general_profile_crosssection 
                ) 
                * calculation_working_setup_quantity_relative
                /100
              )
            ) * o.`general_raw_material_price_total_overwritten`
          ) 
          / (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
          * calculation_working_annual_requirement_estimated
          AS `_pricing_costs_yearly_raw_material_quantity`, 
      -- TODO

      --  Zusatzpreis / m [€] = `pricing_costs_calc_price_additional_lfm` * calculation_quantityA
          ROUND(
            COALESCE(o.`pricing_costs_calc_price_additional_lfm`, 0) * o.`calculation_quantityA`
          ,2 ) AS `_pricing_endprices_calc_price_additional_lfm_total`,

      --  Verpackung = _calculation_additional_box_costs_lfm * calculation_quantityA
          ( ROUND(
            (
              ((o.`calculation_additional_box_count` * o.`calculation_additional_box_price_per_piece`) + 
              o.`calculation_additional_box_price_flat_additional`) / o.`calculation_quantityA`
            )
            * o.`calculation_quantityA`
          ,2 )) AS `_pricing_endprices_calc_packing_costs`,

      --  Transport = _calculation_additional_transport_costs_lfm * calculation_quantityA
          ( ROUND (
            (o.`calculation_additional_transport_costs_total` / o.`calculation_quantityA`)
            * o.`calculation_quantityA`
          ,2 )) AS `_pricing_endprices_calc_transport_costs`,

      --  Druck = _calculation_additional_single_print_lfm * calculation_quantityA
          ( ROUND(
            (o.`calculation_additional_single_print` * o.`calculation_additional_single_print_price`)
            * o.`calculation_quantityA`
          ,2 )) AS `_pricing_endprices_calc_print_costs`,

      --  Konfektion / m = _calculation_processing_lfm_costs * calculation_quantityA
          ( ROUND(
            (
              (o.`calculation_processing_lfm_runtime` * o.`calculation_processing_lfm_runtime_factor` + 
              o.`calculation_processing_lfm_packing_time` * o.`calculation_processing_lfm_packing_time_factor`) *
              o.`calculation_processing_lfm_hourly_rate` / 3600
            )
            * o.`calculation_quantityA`
          ,2 )) AS `_pricing_endprices_calc_confection_lfm_costs`,

      --  Konfektion / stk = _calculation_processing_piece_costs * calculation_quantityA * Aufmachung / 1000
          ( ROUND(
            (
              (o.`calculation_processing_piece_runtime` * o.`calculation_processing_piece_runtime_factor` + 
              o.`calculation_processing_piece_packing_time` * o.`calculation_processing_piece_packing_time_factor`) *
              o.`calculation_processing_piece_hourly_rate` / 3600
            )
            * o.`calculation_quantityA` * o.`general_packaging` / 1000
          ,2 )) AS `_pricing_endprices_calc_confection_stk_costs`,


      --  Staffelpreise Mengen -- graduated prices quantities plus additonal setup quantity
          (o.`calculation_quantityA` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            AS `_pricing_graduated_calculation_quantityA`, 

          (o.`calculation_quantityB` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            AS `_pricing_graduated_calculation_quantityB`, 

          (o.`calculation_quantityC` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            AS `_pricing_graduated_calculation_quantityC`, 

          (o.`calculation_quantityD` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            AS `_pricing_graduated_calculation_quantityD`, 

          (o.`calculation_quantityE` * (100 + o.pricing_graduated_calculation_additional_setup_quantity)/100) 
            AS `_pricing_graduated_calculation_quantityE`,

      --  Staffelpreise Stundensatz -- graduated prices hourly rates
          o.`calculation_working_hourly_rate` 
            AS `_pricing_graduated_calculation_hourly_rate_quantityA`,

          (o.`calculation_working_hourly_rate` * (100 + COALESCE(o.pricing_grad_qtyB_add_hourlyrate,0))/100)
            AS `_pricing_graduated_calculation_hourly_rate_quantityB`,

          (o.`calculation_working_hourly_rate` * (100 + COALESCE(o.pricing_grad_qtyC_add_hourlyrate,0))/100)
            AS `_pricing_graduated_calculation_hourly_rate_quantityC`,

          (o.`calculation_working_hourly_rate` * (100 + COALESCE(o.pricing_grad_qtyD_add_hourlyrate,0))/100)
            AS `_pricing_graduated_calculation_hourly_rate_quantityD`,

          (o.`calculation_working_hourly_rate` * (100 + COALESCE(o.pricing_grad_qtyE_add_hourlyrate,0))/100)
            AS `_pricing_graduated_calculation_hourly_rate_quantityE`,

          (o.`runningcard_hourlyrecording_construction` + o.`runningcard_hourlyrecording_toolwork` + o.`runningcard_hourlyrecording_entry`) AS `_runningcard_hourlyrecording_total`


        FROM `offers` o;
      SQL
    );


    DB::statement(
      <<<SQL
CREATE OR REPLACE VIEW offers_calculated_temp2 AS 
  SELECT 
  -- calculated field from offers::
    o_c.calculation_additional_box_count,
    o_c.calculation_additional_box_price_flat_additional,
    o_c.calculation_additional_box_price_per_piece,
    o_c.calculation_additional_hourly_rate,
    o_c.calculation_additional_setup_time,
    o_c.calculation_additional_single_print,
    o_c.calculation_additional_single_print_price,
    o_c.calculation_additional_transport_costs_total,
    o_c.calculation_processing_lfm_hourly_rate,
    o_c.calculation_processing_lfm_packing_time,
    o_c.calculation_processing_lfm_packing_time_factor,
    o_c.calculation_processing_lfm_runtime,
    o_c.calculation_processing_lfm_runtime_factor,
    o_c.calculation_processing_piece_hourly_rate,
    o_c.calculation_processing_piece_packing_time,
    o_c.calculation_processing_piece_packing_time_factor,
    o_c.calculation_processing_piece_runtime,
    o_c.calculation_processing_piece_runtime_factor,
    o_c.calculation_quantityA,
    o_c.calculation_quantityB,
    o_c.calculation_quantityC,
    o_c.calculation_quantityD,
    o_c.calculation_quantityE,
    o_c.calculation_working_additional_costs,
    o_c.calculation_working_allocation_costs_additional,
    o_c.calculation_working_annual_requirement_estimated,
    o_c.calculation_working_commission,
    o_c.calculation_working_discount,
    o_c.calculation_working_extrusion_speed,
    o_c.calculation_working_hourly_rate,
    o_c.calculation_working_profile_cross_section_deviation_lower,
    o_c.calculation_working_profile_cross_section_deviation_upper,
    o_c.calculation_working_profit,
    o_c.calculation_working_setup_quantity_relative,
    o_c.calculation_working_setup_quantity_total,
    o_c.calculation_working_tool_costs_amortization_years,
    o_c.calculation_working_tool_costs_customer,
    o_c.calculation_working_tool_costs_total,
    o_c.created_at,
    o_c.general_article_number,
    o_c.general_color,
    o_c.general_comments,
    o_c.general_created_by_user_id,
    o_c.general_creation_date,
    o_c.general_customer,
    o_c.general_customer_article_number,
    o_c.general_customer_contact_person,
    o_c.general_delivery_type,
    o_c.general_offer_number,
    o_c.general_order_number,
    o_c.general_packaging,
    o_c.general_profile_crosssection,
    o_c.general_profile_description,
    o_c.general_raw_material_price_total_overwritten,
    o_c.general_raw_material_purchase_discount,
    o_c.general_request_date,
    o_c.general_request_number,
    o_c.general_status_id,
    o_c.general_tool_number,
    o_c.id,
    o_c.pricing_annual_requirement,
    o_c.pricing_costs_calc_price_additional_lfm,
    o_c.pricing_costs_calc_price_additional_lfm_desc,
    o_c.pricing_grad_qtyB_add_hourlyrate,
    o_c.pricing_grad_qtyB_add_setupcosts,
    o_c.pricing_grad_qtyB_add_transport,
    o_c.pricing_grad_qtyC_add_hourlyrate,
    o_c.pricing_grad_qtyC_add_setupcosts,
    o_c.pricing_grad_qtyC_add_transport,
    o_c.pricing_grad_qtyD_add_hourlyrate,
    o_c.pricing_grad_qtyD_add_setupcosts,
    o_c.pricing_grad_qtyD_add_transport,
    o_c.pricing_grad_qtyE_add_hourlyrate,
    o_c.pricing_grad_qtyE_add_setupcosts,
    o_c.pricing_grad_qtyE_add_transport,
    o_c.pricing_graduated_calculation_additional_setup_quantity,
    o_c.pricing_machine_utilization_annual_machine_capacity,
    o_c.runningcard_extrusion_speed_IST,
    o_c.runningcard_hourlyrecording_construction,
    o_c.runningcard_hourlyrecording_entry,
    o_c.runningcard_hourlyrecording_entrydriver_user_id,
    o_c.runningcard_hourlyrecording_entrystitches,
    o_c.runningcard_hourlyrecording_toolmaker_user_id,
    o_c.runningcard_hourlyrecording_toolwork,
    o_c.runningcard_packing_description,
    o_c.runningcard_packing_length,
    o_c.runningcard_packing_packing_unit,
    o_c.runningcard_packing_quantity,
    o_c.runningcard_packing_type,
    o_c.runningcard_packing_variant,
    o_c.runningcard_printing,
    o_c.runningcard_profile_weight_IST,
    o_c.runningcard_qualitity_indication,
    o_c.runningcard_sampling_date,
    o_c.runningcard_sampling_indication,
    o_c.runningcard_sampling_length,
    o_c.runningcard_sampling_packing,
    o_c.runningcard_sampling_quantity,
    o_c.runningcard_tool_cost_type,
    o_c.runningcard_tool_hint,
    o_c._runningcard_hourlyrecording_total,
    o_c.updated_at,
    o_c.pricing_piece_length_prices_length1,
    o_c.pricing_piece_length_prices_length2,
    o_c.pricing_piece_length_prices_length3,
    o_c.pricing_piece_length_prices_length4,
    o_c.pricing_piece_length_prices_length5,
    
    -- calculated field from offers_calculated::    
    o_c._calculation_additional_box_costs_lfm,
    o_c._calculation_additional_setup_costs_lfm,
    o_c._calculation_additional_setup_costs_total,
    o_c._calculation_additional_single_print_lfm,
    o_c._calculation_additional_transport_costs_lfm,
    o_c._calculation_processing_lfm_costs,
    o_c._calculation_processing_lfm_expense,
    o_c._calculation_processing_piece_costs,
    o_c._calculation_processing_piece_expense,
    o_c._calculation_working_allocation_costs_lfm,
    o_c._calculation_working_density_total,
    o_c._calculation_working_profile_weight_average,
    o_c._calculation_working_profile_weight_lowerborder,
    o_c._calculation_working_profile_weight_upperborder,
    o_c._calculation_working_setup_quantity_lfm,
    o_c._calculation_working_setup_time,
    o_c._calculation_working_tool_costs_customer_relative,
    o_c._pricing_costs_calc_production_time,
    o_c._pricing_costs_calc_raw_material_price_total,
    o_c._pricing_costs_calc_raw_material_quantity,
    o_c._pricing_costs_calc_raw_material_quantity_total,
    o_c._pricing_costs_calc_raw_material_setup_quantity,
    o_c._pricing_costs_calc_time_costs_quantity,
    o_c._pricing_costs_yearly_raw_material_quantity,
    o_c._pricing_costs_yearly_time_costs_quantity,
    o_c._pricing_endprices_calc_confection_lfm_costs,
    o_c._pricing_endprices_calc_confection_stk_costs,
    o_c._pricing_endprices_calc_packing_costs,
    o_c._pricing_endprices_calc_price_additional_lfm_total,
    o_c._pricing_endprices_calc_print_costs,
    o_c._pricing_endprices_calc_transport_costs,



-- Zwischensumme1: Zeitkosten + Rohstoffpreis (Kalkmenge)
    (o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total`) AS _zwischensumme1,

-- Zwischensumme2: Zwischensumme1 + Rüstzeit + Verpackung + Transport + Druck
    (o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
    + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
    + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
    AS _zwischensumme2,

-- Zwischensumme2: Zwischensumme1 + Rüstzeit + Verpackung + Transport + Druck
    (
      (o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`
    ) AS _zwischensumme2_meterpreis,

--    Zwischenmeterpreis + Werkzeugkostenumlage + Zusatz (Dichtung, Klebeband / m)
    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) AS _zwischensumme3_meterpreis,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_commission / 100 
    AS _calculation_working_commission_total_lfm,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_commission / 100 * o_c.`calculation_quantityA`
    AS _calculation_working_commission_total,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_profit / 100 
    AS _calculation_working_profit_total_lfm,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_profit / 100 * o_c.`calculation_quantityA`
    AS _calculation_working_profit_total,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_discount / 100 
    AS _calculation_working_discount_total_lfm,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * calculation_working_discount / 100 * o_c.`calculation_quantityA`
    AS _calculation_working_discount_total,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
    AS _calculation_working_discount_profit_commssion_total_lfm,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
     * o_c.`calculation_quantityA`
    AS _calculation_working_discount_profit_commssion_total,

--  Summe
    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) +
    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
    AS `_pricing_endprices_calc_sum_lfm`,

    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * o_c.`calculation_quantityA` +
    (
      ((o_c.`_pricing_costs_calc_time_costs_quantity` + o_c.`_pricing_costs_calc_raw_material_price_total` 
      + o_c.`_calculation_additional_setup_costs_total` + o_c.`_pricing_endprices_calc_packing_costs`
      + o_c.`_pricing_endprices_calc_transport_costs` + o_c.`_pricing_endprices_calc_print_costs`)
      / o_c.`calculation_quantityA`)
      + o_c.`_calculation_working_allocation_costs_lfm`
      + COALESCE(o_c.`pricing_costs_calc_price_additional_lfm`, 0)
    ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 * o_c.`calculation_quantityA`
    AS `_pricing_endprices_calc_sum`,




    o_c._pricing_graduated_calculation_quantityA,
    o_c._pricing_graduated_calculation_quantityB,
    o_c._pricing_graduated_calculation_quantityC,
    o_c._pricing_graduated_calculation_quantityD,
    o_c._pricing_graduated_calculation_quantityE,

    o_c._pricing_graduated_calculation_hourly_rate_quantityA,
    o_c._pricing_graduated_calculation_hourly_rate_quantityB,
    o_c._pricing_graduated_calculation_hourly_rate_quantityC,
    o_c._pricing_graduated_calculation_hourly_rate_quantityD,
    o_c._pricing_graduated_calculation_hourly_rate_quantityE,

--  Staffelpreise Produktionszeit -- graduated prices productiontime = Menge(zzgl)/Extrusionsgeschwi./60
--    (o_c._pricing_graduated_calculation_quantityA / o_c.calculation_working_extrusion_speed / 60) 
    NULL AS `_pricing_graduated_calculation_productiontime_quantityA`,
    (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60)  AS `_pricing_graduated_calculation_productiontime_quantityB`,
    (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60)  AS `_pricing_graduated_calculation_productiontime_quantityC`,
    (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60)  AS `_pricing_graduated_calculation_productiontime_quantityD`,
    (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60)  AS `_pricing_graduated_calculation_productiontime_quantityE`,

--  Staffelpreise Rohstoffmengen -- graduated prices raw material quantities
--  (Profilgewicht / 1000 * calcMenge ) + (Profilgewicht/1000*calcMenge*Einstellmenge/100)
--    (_calculation_working_profile_weight_average / 1000 * _pricing_graduated_calculation_quantityA) +
--    (_calculation_working_profile_weight_average / 1000 * _pricing_graduated_calculation_quantityA*calculation_working_setup_quantity_relative/100)


    NULL AS `_pricing_graduated_calculation_rawmaterialquantity_quantityA`,
    (
      (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityB)
      +
      (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
    ) AS `_pricing_graduated_calculation_rawmaterialquantity_quantityB`,
    (
      (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityC)
      +
      (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
    ) AS `_pricing_graduated_calculation_rawmaterialquantity_quantityC`,
    (
      (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityD)
      +
      (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
    ) AS `_pricing_graduated_calculation_rawmaterialquantity_quantityD`,
    (
      (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityE)
      +
      (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
    ) AS `_pricing_graduated_calculation_rawmaterialquantity_quantityE`,

-- Zeitkosten = Produktionszeit * Stundensatz(neu)
    (
      (o_c._pricing_graduated_calculation_quantityA / o_c.calculation_working_extrusion_speed / 60) * o_c.calculation_working_hourly_rate
    ) AS _pricing_graduated_calculation_timecosts_total_quantityA, 
    (
      (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
    ) AS _pricing_graduated_calculation_timecosts_total_quantityB,
     (
      (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
    ) AS _pricing_graduated_calculation_timecosts_total_quantityC,
     (
      (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
    ) AS _pricing_graduated_calculation_timecosts_total_quantityD,
     (
      (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
    ) AS _pricing_graduated_calculation_timecosts_total_quantityE,

-- Rohstoffpreis = Rohstoffmenge(kg) * general_raw_material_price_total_overwritten
    (
      NULL * o_c.general_raw_material_price_total_overwritten
    ) AS _pricing_graduated_calculation_rawmaterial_costs_quantityA,
    (
      (
        (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityB)
        +
        (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
      ) * o_c.general_raw_material_price_total_overwritten 
    ) AS _pricing_graduated_calculation_rawmaterial_costs_quantityB,
    (
      (
        (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityC)
        +
        (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
      ) * o_c.general_raw_material_price_total_overwritten
    ) AS _pricing_graduated_calculation_rawmaterial_costs_quantityC,
    (
      (
        (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityD)
        +
        (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
      ) * o_c.general_raw_material_price_total_overwritten 
    )  AS _pricing_graduated_calculation_rawmaterial_costs_quantityD,
    (
      (
        (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityE)
        +
        (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
      ) * o_c.general_raw_material_price_total_overwritten 
    ) AS _pricing_graduated_calculation_rawmaterial_costs_quantityE,

--  Staffelpreise Zwischensummen -- graduated prices subtotals =  
--      Zeitkosten (o_c._pricing_graduated_calculation_timecosts_total_quantityB) + Rohstoffpreis (_pricing_graduated_calculation_rawmaterial_costs_quantityB)
    NULL AS `_pricing_graduated_calculation_subtotal_quantityA`,
    (
      (
        (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
      )  
      + 
      (
        (
          (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityB)
          +
          (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
        ) * o_c.general_raw_material_price_total_overwritten 
      )
    ) AS `_pricing_graduated_calculation_subtotal_quantityB`,
    (
      (
        (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
      )  
      + 
      (
        (
          (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityC)
          +
          (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
        ) * o_c.general_raw_material_price_total_overwritten 
      )
    ) AS `_pricing_graduated_calculation_subtotal_quantityC`,
    (
      (
        (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
      )  
      + 
      (
        (
          (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityD)
          +
          (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
        ) * o_c.general_raw_material_price_total_overwritten 
      )
    ) AS `_pricing_graduated_calculation_subtotal_quantityD`,
    (
      (
        (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
      )  
      + 
      (
        (
          (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityE)
          +
          (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
        ) * o_c.general_raw_material_price_total_overwritten 
      )
    ) AS `_pricing_graduated_calculation_subtotal_quantityE`,

-- Zwischensumme 2 = Rüstkosten126 + Transportkosten100 + Zwischensumme 1 + (VerpackungLFM * Menge)100 + (DruckLFM * Menge)10
    NULL AS _pricing_graduated_calculation_subtotal2_quantityA,

    (
      COALESCE(o_c.pricing_grad_qtyB_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyB_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityB + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityB)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityB)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityB, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityB)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityB, 0)

    ) AS _pricing_graduated_calculation_subtotal2_quantityB,

    (
      COALESCE(o_c.pricing_grad_qtyC_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyC_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityC + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityC)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityC)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityC, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityC)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityC, 0)

    ) AS _pricing_graduated_calculation_subtotal2_quantityC,

    (
      COALESCE(o_c.pricing_grad_qtyD_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyD_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityD + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityD)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityD)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityD, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityD)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityD, 0)

    ) AS _pricing_graduated_calculation_subtotal2_quantityD,

    (
      COALESCE(o_c.pricing_grad_qtyE_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyE_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityE + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityE)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityE)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityE, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityE)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityE, 0)

    ) AS _pricing_graduated_calculation_subtotal2_quantityE,

    
    NULL AS `_pricing_graduated_calculation_subtotal_lfm_quantityA`,

--  Staffelpreise Zwischensummen / m -- graduated prices subtotals lfm
    (
      COALESCE(o_c.pricing_grad_qtyB_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyB_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityB + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityB)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityB)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityB, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityB)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityB, 0)

    ) / COALESCE(o_c.calculation_quantityB, 1) AS `_pricing_graduated_calculation_subtotal_lfm_quantityB`,

    (
      COALESCE(o_c.pricing_grad_qtyC_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyC_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityC + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityC)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityC)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityC, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityC)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityC, 0)

    ) / COALESCE(o_c.calculation_quantityC, 1) AS `_pricing_graduated_calculation_subtotal_lfm_quantityC`,

    (
      COALESCE(o_c.pricing_grad_qtyD_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyD_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityD + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityD)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityD)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityD, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityD)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityD, 0)

    ) / COALESCE(o_c.calculation_quantityD, 1) AS `_pricing_graduated_calculation_subtotal_lfm_quantityD`,

    (
      COALESCE(o_c.pricing_grad_qtyE_add_setupcosts, 0) +
      COALESCE(o_c.pricing_grad_qtyE_add_transport, 0) + 
--      _pricing_graduated_calculation_subtotal_quantityE + 
      (
        (
          (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
        )  
        + 
        COALESCE(
          (
            (
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) / 1000 * o_c._pricing_graduated_calculation_quantityE)
              +
              (COALESCE(o_c._calculation_working_profile_weight_upperborder, 0) /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
            ) * COALESCE(o_c.general_raw_material_price_total_overwritten, 0)
          ),
          0
        )
      ) +
-- (_calculation_additional_box_costs_lfm * calculation_quantityE)
      COALESCE(o_c._calculation_additional_box_costs_lfm * o_c.calculation_quantityE, 0) +
-- (_calculation_additional_single_print_lfm * calculation_quantityE)
      COALESCE(o_c._calculation_additional_single_print_lfm * o_c.calculation_quantityE, 0)

    ) / COALESCE(o_c.calculation_quantityE, 1) AS `_pricing_graduated_calculation_subtotal_lfm_quantityE`,


--  Zeitkosten (_pricing_graduated_calculation_timecosts_total_quantityB) / 
--  Zwischensumme1 (_pricing_graduated_calculation_subtotal_quantityB)
    NULL AS _pricing_graduated_calculation_timecosts_relative_quantityA,

    (
      (
        (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
      ) 
      /
      (
        (
          (o_c._pricing_graduated_calculation_quantityB / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityB
        )  
        + 
        (
          (
            (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityB)
            +
            (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityB * o_c.calculation_working_setup_quantity_relative / 100)
          ) * o_c.general_raw_material_price_total_overwritten 
        )
      )
    ) * 100 AS _pricing_graduated_calculation_timecosts_relative_quantityB,

    (
      (
        (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
      ) 
      /
      (
        (
          (o_c._pricing_graduated_calculation_quantityC / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityC
        )  
        + 
        (
          (
            (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityC)
            +
            (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityC * o_c.calculation_working_setup_quantity_relative / 100)
          ) * o_c.general_raw_material_price_total_overwritten 
        )
      )
    ) * 100 AS _pricing_graduated_calculation_timecosts_relative_quantityC,

    (
      (
        (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
      ) 
      /
      (
        (
          (o_c._pricing_graduated_calculation_quantityD / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityD
        )  
        + 
        (
          (
            (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityD)
            +
            (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityD * o_c.calculation_working_setup_quantity_relative / 100)
          ) * o_c.general_raw_material_price_total_overwritten 
        )
      )
    ) * 100 AS _pricing_graduated_calculation_timecosts_relative_quantityD,

    (
      (
        (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
      ) 
      /
      (
        (
          (o_c._pricing_graduated_calculation_quantityE / o_c.calculation_working_extrusion_speed / 60) *  o_c._pricing_graduated_calculation_hourly_rate_quantityE
        )  
        + 
        (
          (
            (o_c._calculation_working_profile_weight_upperborder / 1000 * o_c._pricing_graduated_calculation_quantityE)
            +
            (o_c._calculation_working_profile_weight_upperborder /  1000 * o_c._pricing_graduated_calculation_quantityE * o_c.calculation_working_setup_quantity_relative / 100)
          ) * o_c.general_raw_material_price_total_overwritten 
        )
      )
    ) * 100 AS _pricing_graduated_calculation_timecosts_relative_quantityE


    FROM offers_calculated_temp o_c
SQL
    );

    DB::statement(
      <<<SQL
      CREATE OR REPLACE VIEW offers_calculated_temp3 AS
        SELECT 
          oc.*,

      --  Staffel / m
          (
            ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
            + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
            + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
            / oc.`calculation_quantityA`)
            + oc.`_calculation_working_allocation_costs_lfm`
            + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
          ) +
          (
            ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
            + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
            + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
            / oc.`calculation_quantityA`)
            + oc.`_calculation_working_allocation_costs_lfm`
            + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
          ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
          AS `_pricing_endprices_graduated_without_confection_lfm_quantityA`,

      --  (ZwischenmeterpreisLFM_B + WerkzeugkostenumlageLFM + ZusatzkostenLFM) + 
      --  (ZwischenmeterpreisLFM_B + WerkzeugkostenumlageLFM + ZusatzkostenLFM) * (Provision + Gewinn + Zahlungsziel) / 100
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) AS `_pricing_endprices_graduated_without_confection_lfm_quantityB`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) AS `_pricing_endprices_graduated_without_confection_lfm_quantityC`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) AS `_pricing_endprices_graduated_without_confection_lfm_quantityD`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) AS `_pricing_endprices_graduated_without_confection_lfm_quantityE`,


      --  Staffel / stk =   _pricing_endprices_graduated_without_confection_lfm_quantityA / general_packaging / 1000
          (
            (
              (
                ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                / oc.`calculation_quantityA`)
                + oc.`_calculation_working_allocation_costs_lfm`
                + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
              ) +
              (
                ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                / oc.`calculation_quantityA`)
                + oc.`_calculation_working_allocation_costs_lfm`
                + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
              ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
            ) * oc.general_packaging / 1000      
          ) AS `_pricing_endprices_graduated_without_confection_stk_quantityA`,
          
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) * oc.general_packaging / 1000 
          AS `_pricing_endprices_graduated_without_confection_stk_quantityB`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) * oc.general_packaging / 1000 
          AS `_pricing_endprices_graduated_without_confection_stk_quantityC`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) * oc.general_packaging / 1000 
          AS `_pricing_endprices_graduated_without_confection_stk_quantityD`,
          (
            (_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) + 
            ((_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(pricing_costs_calc_price_additional_lfm, 0)) *
            (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
          ) * oc.general_packaging / 1000 
          AS `_pricing_endprices_graduated_without_confection_stk_quantityE`,


      --  Stück = Menge / Aufmachung * 1000
          CEILING (
            oc.calculation_quantityA / oc.general_packaging * 1000
          )  AS `_pricing_endprices_graduated_pieces_quantityA`,
          CEILING (
            oc.calculation_quantityB / oc.general_packaging * 1000
          )  AS `_pricing_endprices_graduated_pieces_quantityB`,
          CEILING (
            oc.calculation_quantityC / oc.general_packaging * 1000
          )  AS `_pricing_endprices_graduated_pieces_quantityC`,
          CEILING (
            oc.calculation_quantityD / oc.general_packaging * 1000
          )  AS `_pricing_endprices_graduated_pieces_quantityD`,
          CEILING (
            oc.calculation_quantityE / oc.general_packaging * 1000
          )  AS `_pricing_endprices_graduated_pieces_quantityE`,


      --  Staffel / m = Staffel / stk / general_packaging * 1000
          (
            (
              (
                (
                  ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                  + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                  + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                  / oc.`calculation_quantityA`)
                  + oc.`_calculation_working_allocation_costs_lfm`
                  + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
                ) +
                (
                  ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                  + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                  + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                  / oc.`calculation_quantityA`)
                  + oc.`_calculation_working_allocation_costs_lfm`
                  + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
                ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
              ) * oc.general_packaging / 1000      
            ) 
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) / oc.general_packaging * 1000 
          AS `_pricing_endprices_graduated_with_confection_lfm_quantityA`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) / oc.general_packaging * 1000 
          AS `_pricing_endprices_graduated_with_confection_lfm_quantityB`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) / oc.general_packaging * 1000 
          AS `_pricing_endprices_graduated_with_confection_lfm_quantityC`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          )  / oc.general_packaging * 1000 
          AS `_pricing_endprices_graduated_with_confection_lfm_quantityD`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) / oc.general_packaging * 1000 
          AS `_pricing_endprices_graduated_with_confection_lfm_quantityE`,


      --  Staffel / stk = _pricing_endprices_graduated_without_confection_stk_quantityA
      --  + _calculation_processing_piece_costs
      --  + (_calculation_processing_lfm_costs * general_packaging / 1000)

          (
            (
              (
                (
                  ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                  + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                  + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                  / oc.`calculation_quantityA`)
                  + oc.`_calculation_working_allocation_costs_lfm`
                  + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
                ) +
                (
                  ((oc.`_pricing_costs_calc_time_costs_quantity` + oc.`_pricing_costs_calc_raw_material_price_total` 
                  + oc.`_calculation_additional_setup_costs_total` + oc.`_pricing_endprices_calc_packing_costs`
                  + oc.`_pricing_endprices_calc_transport_costs` + oc.`_pricing_endprices_calc_print_costs`)
                  / oc.`calculation_quantityA`)
                  + oc.`_calculation_working_allocation_costs_lfm`
                  + COALESCE(oc.`pricing_costs_calc_price_additional_lfm`, 0)
                ) * (calculation_working_discount + calculation_working_profit + calculation_working_commission) / 100 
              ) * oc.general_packaging / 1000      
            ) 
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) AS `_pricing_endprices_graduated_with_confection_stk_quantityA`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityB + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) AS `_pricing_endprices_graduated_with_confection_stk_quantityB`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityC + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) AS `_pricing_endprices_graduated_with_confection_stk_quantityC`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityD + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) AS `_pricing_endprices_graduated_with_confection_stk_quantityD`,
          (
            (
              (
                (_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) + 
                ((_pricing_graduated_calculation_subtotal_lfm_quantityE + _calculation_working_allocation_costs_lfm + COALESCE(oc.pricing_costs_calc_price_additional_lfm, 0)) *
                (calculation_working_commission + calculation_working_profit + calculation_working_discount) / 100)
              ) * oc.general_packaging / 1000
            )
            + _calculation_processing_piece_costs
            + (_calculation_processing_lfm_costs * general_packaging / 1000)
          ) AS `_pricing_endprices_graduated_with_confection_stk_quantityE`,



      -- Maschinenauslastung->Stunden -- machine utilization->hours

      -- calculation_working_annual_requirement_estimated * pricing_graduated_calculation_additional_setup_quantity 
      --    / calculation_working_extrusion_speed / 60
          (
            (oc.calculation_working_annual_requirement_estimated * (100+oc.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantity_yearly`,

      -- Menge(zzgl) / Extrusionsgeschwindigkeit / 60
          (
            oc._pricing_graduated_calculation_quantityA / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantityA`,
          (
            oc._pricing_graduated_calculation_quantityB / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantityB`,
          (
            oc._pricing_graduated_calculation_quantityC / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantityC`,
          (
            oc._pricing_graduated_calculation_quantityD / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantityD`,
          (
            oc._pricing_graduated_calculation_quantityE / oc.calculation_working_extrusion_speed / 60
          ) AS `_pricing_machine_utilization_hours_quantityE`,


      -- Maschinenauslastung->Tage -- machine utilization->days
          (
            (oc.calculation_working_annual_requirement_estimated * (100+oc.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / oc.calculation_working_extrusion_speed / 60
          )  / 24 AS `_pricing_machine_utilization_days_quantity_yearly`,
          (
            oc._pricing_graduated_calculation_quantityA / oc.calculation_working_extrusion_speed / 60
          ) / 24 AS `_pricing_machine_utilization_days_quantityA`,
          (
            oc._pricing_graduated_calculation_quantityB / oc.calculation_working_extrusion_speed / 60
          ) / 24 AS `_pricing_machine_utilization_days_quantityB`,
          (
            oc._pricing_graduated_calculation_quantityC / oc.calculation_working_extrusion_speed / 60
          ) / 24  AS `_pricing_machine_utilization_days_quantityC`,
          (
            oc._pricing_graduated_calculation_quantityD / oc.calculation_working_extrusion_speed / 60
          ) / 24  AS `_pricing_machine_utilization_days_quantityD`,
          (
            oc._pricing_graduated_calculation_quantityE / oc.calculation_working_extrusion_speed / 60
          ) / 24  AS `_pricing_machine_utilization_days_quantityE`,


      -- Maschinenauslastung->Wochen -- machine utilization->weeks
          (
            (oc.calculation_working_annual_requirement_estimated * (100+oc.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / oc.calculation_working_extrusion_speed / 60
          )  / 24 / 5 AS `_pricing_machine_utilization_weeks_quantity_yearly`,
          (
            oc._pricing_graduated_calculation_quantityA / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 AS `_pricing_machine_utilization_weeks_quantityA`,
          (
            oc._pricing_graduated_calculation_quantityB / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 AS `_pricing_machine_utilization_weeks_quantityB`,
          (
            oc._pricing_graduated_calculation_quantityC / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 AS `_pricing_machine_utilization_weeks_quantityC`,
          (
            oc._pricing_graduated_calculation_quantityD / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 AS `_pricing_machine_utilization_weeks_quantityD`,
          (
            oc._pricing_graduated_calculation_quantityE / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 AS `_pricing_machine_utilization_weeks_quantityE`,


      -- Maschinenauslastung->Monate -- machine utilization->months
          (
            (oc.calculation_working_annual_requirement_estimated * (100+oc.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / oc.calculation_working_extrusion_speed / 60
          )  / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantity_yearly`,
          (
            oc._pricing_graduated_calculation_quantityA / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantityA`,
          (
            oc._pricing_graduated_calculation_quantityB / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantityB`,
          (
            oc._pricing_graduated_calculation_quantityC / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantityC`,
          (
            oc._pricing_graduated_calculation_quantityD / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantityD`,
          (
            oc._pricing_graduated_calculation_quantityE / oc.calculation_working_extrusion_speed / 60
          ) / 24 / 5 / 4 AS `_pricing_machine_utilization_months_quantityE`,


      -- Maschinenauslastung->Maschinenauslastung im Jahr prozentual -- machine utilization per year relative
          (
            (oc.calculation_working_annual_requirement_estimated * (100+oc.pricing_graduated_calculation_additional_setup_quantity)/100) 
            / oc.calculation_working_extrusion_speed / 60
          ) / oc.pricing_machine_utilization_annual_machine_capacity * 100  
          AS `_pricing_machine_utilization_yearly_relative`

        FROM offers_calculated_temp2 oc;
  SQL
    );
  }

  public function down(): void
  {
    DB::statement('DROP VIEW IF EXISTS offers_calculated');
  }
}
