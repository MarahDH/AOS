import CardBox from "@components/CardBox";
import FormDatePicker from "@components/FormDatePicker";
import FormIntField from "@components/FormInputs/FormIntField";
import FormTextField from "@components/FormInputs/FormTextField";
import Grid from "@mui/material/Grid2";
import NoteCard from "@components/NoteCard";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent } from "react";
import { useFieldEditable } from "@hooks/useFieldEditable";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

const SamplingCard: FunctionComponent = () => {
  const { offerDetails, offerId } = useOfferContext();
  // Permissions
  const { isFieldEditable } = useFieldEditable(offerId!);
  const { canEdit } = usePermissions();
  const isEditable = canEdit("process_sheet");
  const mapInitialValues = (offer: any) => ({
    runningcard_sampling_date: offer.runningcard_sampling_date ?? "",
    runningcard_sampling_quantity: offer.runningcard_sampling_quantity ?? "",
    runningcard_sampling_length: offer.runningcard_sampling_length ?? "",
    runningcard_sampling_packing: offer.runningcard_sampling_packing ?? "",
    runningcard_sampling_indication:
      offer.runningcard_sampling_indication ?? "",
    runningcard_qualitity_indication:
      offer.runningcard_qualitity_indication ?? "",
    runningcard_printing: offer.runningcard_printing ?? "",
  });

  const formik = useFormik({
    initialValues: offerDetails
      ? mapInitialValues(offerDetails)
      : {
          runningcard_sampling_date: "",
          runningcard_sampling_quantity: "",
          runningcard_sampling_length: "",
          runningcard_sampling_packing: "",
          runningcard_sampling_indication: "",
          runningcard_qualitity_indication: "",
          runningcard_printing: "",
        },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Bemusterung">
        <Grid container spacing={2}>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormDatePicker
              name="runningcard_sampling_date"
              label="Termin Bemusterung"
              disabled={
                !isFieldEditable("runningcard_sampling_date") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormIntField
              name="runningcard_sampling_quantity"
              label="Menge"
              disabled={
                !isFieldEditable("runningcard_sampling_quantity") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormIntField
              name="runningcard_sampling_length"
              label="Länge [mm]"
              disabled={
                !isFieldEditable("runningcard_sampling_length") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormTextField
              name="runningcard_sampling_packing"
              label="Verpackung"
              disabled={
                !isFieldEditable("runningcard_sampling_packing") || !isEditable
              }
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2.4 }}>
            <FormTextField
              name="runningcard_sampling_indication"
              label="Hinweis"
              disabled={
                !isFieldEditable("runningcard_sampling_indication") ||
                !isEditable
              }
            />
          </Grid>
        </Grid>
      </CardBox>

      <NoteCard
        field="runningcard_qualitity_indication"
        label="Qualitätshinweis"
        disabled={
          !isFieldEditable("runningcard_qualitity_indication") || !isEditable
        }
      />

      <NoteCard
        field="runningcard_printing"
        label="Bedruckung"
        disabled={!isFieldEditable("runningcard_printing") || !isEditable}
      />
    </FormikProvider>
  );
};

export default SamplingCard;
