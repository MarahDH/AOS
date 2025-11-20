import CardBox from "@components/CardBox";
import FormIntField from "@components/FormInputs/FormIntField";
import FormTextField from "@components/FormInputs/FormTextField";
import Grid from "@mui/material/Grid2";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent } from "react";
import { useFieldEditable } from "@hooks/useFieldEditable";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

const PackagingSeriesCard: FunctionComponent = () => {
  const { offerDetails, offerId } = useOfferContext();
  // Permissions
  const { isFieldEditable } = useFieldEditable(offerId!);
  const { canEdit } = usePermissions();
  const isEditable = canEdit("process_sheet");
  const formik = useFormik({
    initialValues: {
      runningcard_packing_type: offerDetails?.runningcard_packing_type ?? "",
      runningcard_packing_variant:
        offerDetails?.runningcard_packing_variant ?? "",
      runningcard_packing_length:
        offerDetails?.runningcard_packing_length ?? "",
      runningcard_packing_packing_unit:
        offerDetails?.runningcard_packing_packing_unit ?? "",
      runningcard_packing_quantity:
        offerDetails?.runningcard_packing_quantity ?? "",
      runningcard_packing_description:
        offerDetails?.runningcard_packing_description ?? "",
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Verpackung Serie">
        <Grid container spacing={2}>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormTextField
              name="runningcard_packing_type"
              label="Verpackungsart"
              disabled={
                !isFieldEditable("runningcard_packing_type") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormTextField
              name="runningcard_packing_variant"
              label="Verpackungsvariante"
              disabled={
                !isFieldEditable("runningcard_packing_variant") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormIntField
              name="runningcard_packing_length"
              label="Länge [mm]"
              disabled={
                !isFieldEditable("runningcard_packing_length") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormIntField
              name="runningcard_packing_packing_unit"
              label="Packeinheit"
              disabled={
                !isFieldEditable("runningcard_packing_packing_unit") ||
                !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormIntField
              name="runningcard_packing_quantity"
              label="Menge / Palette-Box-Karton"
              disabled={
                !isFieldEditable("runningcard_packing_quantity") || !isEditable
              }
            />
          </Grid>

          <Grid size={{ xs: 12 }}>
            <FormTextField
              name="runningcard_packing_description"
              label="Anmerkung"
              disabled={
                !isFieldEditable("runningcard_packing_description") ||
                !isEditable
              }
              multiline
              minRows={3}
              fullWidth
            />
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default PackagingSeriesCard;
