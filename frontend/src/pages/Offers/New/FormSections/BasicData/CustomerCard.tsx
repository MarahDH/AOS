import CardBox from "@components/CardBox";
import FormDatePicker from "@components/FormDatePicker";
import FormTextField from "@components/FormInputs/FormTextField";
import Grid from "@mui/material/Grid2";
import { CARD_HEIGHT } from "@utils/constantValue";
import { CustomerCardInitialValues } from "../../Index";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent } from "react";
import { useEditableFields } from "@hooks/useEditableFields";
import { useOfferContext } from "@contexts/OfferProvider";

const CustomerCard: FunctionComponent = () => {
  // Hooks
  const { offerDetails, offerId } = useOfferContext();
  // Permissions
  const { data: editableFields = [] } = useEditableFields(offerId!);

  const isFieldEditable = (fieldName: string) =>
    editableFields.includes(fieldName);

  const mapOfferToCustomerInitialValues = (offer: any) => ({
    general_customer: offer.general_customer ?? "",
    general_customer_contact_person:
      offer.general_customer_contact_person ?? "",
    general_customer_article_number:
      offer.general_customer_article_number ?? "",
    general_request_date: offer.general_request_date ?? "",
    general_request_number: offer.general_request_number ?? "",
  });

  const formik = useFormik({
    initialValues: {
      ...CustomerCardInitialValues,
      ...(offerDetails ? mapOfferToCustomerInitialValues(offerDetails) : {}),
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Kunde" height={CARD_HEIGHT}>
        <Grid container spacing={2}>
          <Grid size={{ xs: 6, md: 6 }}>
            <FormTextField
              name="general_customer"
              label="Kunde"
              disabled={!isFieldEditable("general_customer")}
            />
          </Grid>
          <Grid size={{ xs: 6, md: 6 }}>
            <FormDatePicker
              name="general_request_date"
              label="Anfrage vom"
              required
              disabled={!isFieldEditable("general_request_date")}
            />
          </Grid>
          <Grid size={{ xs: 6, md: 6 }}>
            <FormTextField
              name="general_customer_contact_person"
              label="Ansprechpartner"
              disabled={!isFieldEditable("general_customer_contact_person")}
            />
          </Grid>
          <Grid size={{ xs: 6, md: 6 }}>
            <FormTextField
              name="general_request_number"
              label="Anfragenummer"
              disabled={!isFieldEditable("general_request_number")}
            />
          </Grid>
          <Grid size={{ xs: 6, md: 6 }}>
            <FormTextField
              name="general_customer_article_number"
              label="Artikelnummer Kunde"
              disabled={!isFieldEditable("general_customer_article_number")}
            />
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default CustomerCard;
