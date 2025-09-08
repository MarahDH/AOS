<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // $table->enum('general_status', ['Vorkalkulation', 'Angebot', 'Auftrag', 'Produziert', 'Versandt', 'Gelöscht'])->nullable();
            $table->string('general_offer_number', 63)->nullable();
            $table->string('general_profile_description', 63)->nullable();
            $table->dateTime('general_creation_date');
            $table->foreignId('general_status_id')->constrained('offer_status')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('general_created_by_user_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('general_color', 31)->nullable();
            $table->integer('general_packaging')->nullable();
            $table->string('general_tool_number', 31)->nullable();
            $table->string('general_order_number', 31)->nullable();
            $table->enum('general_delivery_type', ['frei', 'unfrei'])->nullable();
            $table->string('general_article_number', 63)->nullable();
            $table->string('general_customer', 63)->nullable();
            $table->string('general_customer_contact_person', 63)->nullable();
            $table->string('general_customer_article_number', 31)->nullable();
            $table->date('general_request_date')->nullable();
            $table->integer('general_request_number')->nullable();
            $table->float('general_profile_crosssection')->nullable();
            $table->float('general_raw_material_price_total_overwritten')->nullable();
            $table->float('general_raw_material_purchase_discount')->nullable();
            $table->text('general_comments')->nullable();

            $table->unsignedInteger('calculation_quantityA')->nullable();
            $table->unsignedInteger('calculation_quantityB')->nullable();
            $table->unsignedInteger('calculation_quantityC')->nullable();
            $table->unsignedInteger('calculation_quantityD')->nullable();
            $table->unsignedInteger('calculation_quantityE')->nullable();

            $table->float('calculation_processing_lfm_hourly_rate')->nullable();
            $table->float('calculation_processing_piece_hourly_rate')->nullable();
            $table->float('calculation_processing_lfm_runtime')->nullable();
            $table->float('calculation_processing_piece_runtime')->nullable();
            $table->integer('calculation_processing_lfm_runtime_factor')->nullable();
            $table->integer('calculation_processing_piece_runtime_factor')->nullable();
            $table->float('calculation_processing_lfm_packing_time')->nullable();
            $table->float('calculation_processing_piece_packing_time')->nullable();
            $table->integer('calculation_processing_lfm_packing_time_factor')->nullable();
            $table->integer('calculation_processing_piece_packing_time_factor')->nullable();
            $table->integer('calculation_additional_setup_time')->nullable();
            $table->float('calculation_additional_hourly_rate')->nullable();
            $table->float('calculation_additional_transport_costs_total')->nullable();
            $table->integer('calculation_additional_box_count')->nullable();
            $table->float('calculation_additional_box_price_per_piece')->nullable();
            $table->float('calculation_additional_box_price_flat_additional')->nullable();
            $table->float('calculation_additional_single_print')->nullable();
            $table->float('calculation_additional_single_print_price')->nullable();
            $table->integer('calculation_working_setup_quantity_relative')->nullable();
            $table->float('calculation_working_extrusion_speed')->nullable();
            $table->integer('calculation_working_annual_requirement_estimated')->nullable();
            $table->float('calculation_working_tool_costs_total')->nullable();
            $table->float('calculation_working_tool_costs_customer')->nullable();
            $table->integer('calculation_working_tool_costs_amortization_years')->nullable();
            $table->integer('calculation_working_allocation_costs_additional')->nullable();
            $table->integer('calculation_working_profile_cross_section_deviation_lower')->default(1);
            $table->integer('calculation_working_profile_cross_section_deviation_upper')->default(1);
            $table->integer('calculation_working_setup_quantity_total')->nullable();
            $table->integer('calculation_working_hourly_rate')->nullable();
            // $table->integer('calculation_working_setup_quantity_additional')->nullable();

            $table->float('calculation_working_additional_costs')->nullable();
            $table->float('calculation_working_commission')->nullable();
            $table->float('calculation_working_profit')->nullable();
            $table->float('calculation_working_discount')->nullable();

            $table->integer('pricing_annual_requirement')->default(0);
            $table->integer('pricing_graduated_calculation_additional_setup_quantity')->default(0);
            $table->float('pricing_costs_calc_price_additional_lfm')->nullable();
            $table->string('pricing_costs_calc_price_additional_lfm_desc', 31)->nullable();

            $table->integer('pricing_grad_qtyB_add_hourlyrate')->nullable();
            $table->integer('pricing_grad_qtyC_add_hourlyrate')->nullable();
            $table->integer('pricing_grad_qtyD_add_hourlyrate')->nullable();
            $table->integer('pricing_grad_qtyE_add_hourlyrate')->nullable();



            $table->unsignedInteger('pricing_grad_qtyB_add_setupcosts')->nullable();
            $table->unsignedInteger('pricing_grad_qtyC_add_setupcosts')->nullable();
            $table->unsignedInteger('pricing_grad_qtyD_add_setupcosts')->nullable();
            $table->unsignedInteger('pricing_grad_qtyE_add_setupcosts')->nullable();

            $table->unsignedInteger('pricing_grad_qtyB_add_transport')->nullable();
            $table->unsignedInteger('pricing_grad_qtyC_add_transport')->nullable();
            $table->unsignedInteger('pricing_grad_qtyD_add_transport')->nullable();
            $table->unsignedInteger('pricing_grad_qtyE_add_transport')->nullable();

            $table->integer('pricing_machine_utilization_annual_machine_capacity')->nullable();

            $table->integer('pricing_piece_length_prices_length1')->nullable();
            $table->integer('pricing_piece_length_prices_length2')->nullable();
            $table->integer('pricing_piece_length_prices_length3')->nullable();
            $table->integer('pricing_piece_length_prices_length4')->nullable();
            $table->integer('pricing_piece_length_prices_length5')->nullable();


            // Running Card
            $table->integer('runningcard_extrusion_speed_IST')->nullable();
            $table->integer('runningcard_profile_weight_IST')->nullable();
            $table->date('runningcard_sampling_date')->nullable();
            $table->integer('runningcard_sampling_quantity')->nullable();
            $table->integer('runningcard_sampling_length')->nullable();
            $table->integer('runningcard_sampling_packing')->nullable();
            $table->string('runningcard_sampling_indication', 31)->nullable();
            $table->text('runningcard_qualitity_indication')->nullable();
            $table->text('runningcard_printing')->nullable();

            $table->string('runningcard_packing_type', 31)->nullable();
            $table->string('runningcard_packing_variant', 31)->nullable();
            $table->integer('runningcard_packing_length')->nullable();
            $table->integer('runningcard_packing_packing_unit')->nullable();
            $table->integer('runningcard_packing_quantity')->nullable();
            $table->text('runningcard_packing_description')->nullable();
            $table->integer('runningcard_hourlyrecording_construction')->nullable();
            $table->integer('runningcard_hourlyrecording_toolwork')->nullable();
            $table->integer('runningcard_hourlyrecording_entry')->nullable();
            $table->integer('runningcard_hourlyrecording_entrystitches')->nullable();

            $table->unsignedBigInteger('runningcard_hourlyrecording_entrydriver_user_id')->nullable();
            $table->unsignedBigInteger('runningcard_hourlyrecording_toolmaker_user_id')->nullable();

            $table->unsignedInteger('runningcard_tool_costs')->nullable();
            // $table->string('runningcard_tool_cost_type', 31)->nullable();
            $table->text('runningcard_tool_hint')->nullable();

            // timestamps if needed (created_at, updated_at)
            // $table->foreign('general_created_by_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('runningcard_hourlyrecording_entrydriver_user_id')->references('id')->on('users')->nullOnDelete()->restrictOnUpdate();
            $table->foreign('runningcard_hourlyrecording_toolmaker_user_id')->references('id')->on('users')->nullOnDelete()->restrictOnUpdate();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
