import CardBox from "@components/CardBox";
import FormIntField from "@components/FormInputs/FormIntField";
import Grid from "@mui/material/Grid2";
import TieredPriceCalculationTable from "./TieredPriceCalculationTable";
import { FormikProvider, useFormik } from "formik";
import { mapStaffelPricedataFromOffer } from ".";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

const toFormikString = (v: unknown) =>
  v === null || v === undefined ? "" : String(v);

const initValue = (target: unknown, source?: unknown) => {
  if (target !== undefined && target !== null && target !== "") {
    return String(target);
  }
  if (typeof source === "number" && !Number.isNaN(source)) {
    return source.toString();
  }
  if (typeof source === "string" && source !== "") {
    return source;
  }
  return "";
};

export interface TieredPriceFormValues {
  pricing_graduated_calculation_additional_setup_quantity: string;
  pricing_grad_qtyB_add_hourlyrate: string;
  pricing_grad_qtyC_add_hourlyrate: string;
  pricing_grad_qtyD_add_hourlyrate: string;
  pricing_grad_qtyE_add_hourlyrate: string;

  pricing_grad_qtyB_add_setupcosts: string;
  pricing_grad_qtyC_add_setupcosts: string;
  pricing_grad_qtyD_add_setupcosts: string;
  pricing_grad_qtyE_add_setupcosts: string;

  pricing_grad_qtyB_add_transport: string;
  pricing_grad_qtyC_add_transport: string;
  pricing_grad_qtyD_add_transport: string;
  pricing_grad_qtyE_add_transport: string;
}

const TieredPriceCalculationCard = () => {
  const { offerDetails } = useOfferContext();
  // Permissions
  const { canEdit } = usePermissions();
  const isEditable = canEdit("prices");

  const mappedData = offerDetails
    ? mapStaffelPricedataFromOffer(offerDetails)
    : [];

  const formik = useFormik<TieredPriceFormValues>({
    initialValues: {
      pricing_graduated_calculation_additional_setup_quantity: toFormikString(
        offerDetails?.pricing_graduated_calculation_additional_setup_quantity
      ),

      pricing_grad_qtyB_add_hourlyrate: toFormikString(
        offerDetails?.pricing_grad_qtyB_add_hourlyrate
      ),
      pricing_grad_qtyC_add_hourlyrate: toFormikString(
        offerDetails?.pricing_grad_qtyC_add_hourlyrate
      ),
      pricing_grad_qtyD_add_hourlyrate: toFormikString(
        offerDetails?.pricing_grad_qtyD_add_hourlyrate
      ),
      pricing_grad_qtyE_add_hourlyrate: toFormikString(
        offerDetails?.pricing_grad_qtyE_add_hourlyrate
      ),

      // Setup cost fields: initialize with _calculation_additional_setup_costs_total
      pricing_grad_qtyB_add_setupcosts: initValue(
        offerDetails?.pricing_grad_qtyB_add_setupcosts,
        offerDetails?._calculation_additional_setup_costs_total
      ),
      pricing_grad_qtyC_add_setupcosts: initValue(
        offerDetails?.pricing_grad_qtyC_add_setupcosts,
        offerDetails?._calculation_additional_setup_costs_total
      ),
      pricing_grad_qtyD_add_setupcosts: initValue(
        offerDetails?.pricing_grad_qtyD_add_setupcosts,
        offerDetails?._calculation_additional_setup_costs_total
      ),
      pricing_grad_qtyE_add_setupcosts: initValue(
        offerDetails?.pricing_grad_qtyE_add_setupcosts,
        offerDetails?._calculation_additional_setup_costs_total
      ),

      // Transport cost fields: initialize with calculation_additional_transport_costs_total
      pricing_grad_qtyB_add_transport: initValue(
        offerDetails?.pricing_grad_qtyB_add_transport,
        offerDetails?.calculation_additional_transport_costs_total
      ),
      pricing_grad_qtyC_add_transport: initValue(
        offerDetails?.pricing_grad_qtyC_add_transport,
        offerDetails?.calculation_additional_transport_costs_total
      ),
      pricing_grad_qtyD_add_transport: initValue(
        offerDetails?.pricing_grad_qtyD_add_transport,
        offerDetails?.calculation_additional_transport_costs_total
      ),
      pricing_grad_qtyE_add_transport: initValue(
        offerDetails?.pricing_grad_qtyE_add_transport,
        offerDetails?.calculation_additional_transport_costs_total
      ),
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <>
      <FormikProvider value={formik}>
        <CardBox label="Staffelpreisberechnung">
          <Grid container spacing={5} display="flex" justifyContent="end">
            <Grid size={{ xs: 12, md: 2 }}>
              <FormIntField
                name="pricing_graduated_calculation_additional_setup_quantity"
                label="zus. Einstellmenge [%]"
                disabled={!isEditable}
              />
            </Grid>
            <Grid size={{ xs: 12, md: 12 }}>
              <TieredPriceCalculationTable
                data={mappedData}
                isEditable={isEditable}
              />
            </Grid>
          </Grid>
        </CardBox>
      </FormikProvider>
    </>
  );
};

export default TieredPriceCalculationCard;
