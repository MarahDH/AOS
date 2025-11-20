import CardBox from "@components/CardBox";
import FormFloatField from "@components/FormInputs/FormFloatField";
import Grid from "@mui/material/Grid2";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent } from "react";
import { useFieldEditable } from "@hooks/useFieldEditable";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

const ProcessingCard: FunctionComponent = () => {
  const { offerDetails, offerId } = useOfferContext();
  // Permissions
  const { isFieldEditable } = useFieldEditable(offerId!);
  const { canEdit } = usePermissions();
  const isEditable = canEdit("process_sheet");

  const mapInitialValues = (offer: any) => ({
    runningcard_extrusion_speed_IST:
      offer.runningcard_extrusion_speed_IST ?? "",
    runningcard_profile_weight_IST: offer.runningcard_profile_weight_IST ?? "",
  });

  const formik = useFormik({
    initialValues: offerDetails
      ? mapInitialValues(offerDetails)
      : {
          runningcard_extrusion_speed_IST: "",
        },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <>
      <FormikProvider value={formik}>
        <CardBox label="Verarbeitung">
          <Grid container spacing={2}>
            <Grid size={{ xs: 12, md: 2.4 }}>
              <FormFloatField
                name="runningcard_extrusion_speed_IST"
                label="Geschwindigkeit Eingefahren (IST) [m/min]"
                disabled={
                  !isFieldEditable("runningcard_extrusion_speed_IST") ||
                  !isEditable
                }
              />
            </Grid>
            <Grid size={{ xs: 12, md: 2.4 }}>
              <FormFloatField
                name="runningcard_profile_weight_IST"
                label="Metergewicht Eingefahren (IST) [g/m]"
                disabled={
                  !isFieldEditable("runningcard_profile_weight_IST") ||
                  !isEditable
                }
              />
            </Grid>
          </Grid>
        </CardBox>
      </FormikProvider>
    </>
  );
};

export default ProcessingCard;
