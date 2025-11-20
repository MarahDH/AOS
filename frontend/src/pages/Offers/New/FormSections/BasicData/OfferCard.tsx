import CardBox from "@components/CardBox";
import FormFloatField from "@components/FormInputs/FormFloatField";
import FormIntField from "@components/FormInputs/FormIntField";
import FormSelectSaveField from "@components/FormSelectSaveField";
import FormTextField from "@components/FormInputs/FormTextField";
import Grid from "@mui/material/Grid2";
import { CARD_HEIGHT } from "@utils/constantValue";
import { DeliveryTypeLabels } from "@enums/GeneralEnums";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent, useEffect } from "react";
import { OfferCardInitialValues } from "../../Index";
import { Skeleton } from "@mui/material";
import { useEditableFields } from "@hooks/useEditableFields";
import { useFieldEditable } from "@hooks/useFieldEditable";
import { useOfferData } from "@hooks/useOfferData";
import { useOfferContext } from "@contexts/OfferProvider";

const OfferCard: FunctionComponent = () => {
  // Hooks
  const { offerDetails, offerId } = useOfferContext();
  // Permissions
  const { refetch: refetchEditableFields } = useEditableFields(offerId!);
  const { isFieldEditable } = useFieldEditable(offerId!);

  // Dropdowns - use consolidated data
  const { data: offerData } = useOfferData(offerId!);
  const statusOptions = offerData?.offer_statuses?.map((status: any) => ({
    value: status.id,
    label: status.name,
  })) || [];

  const deliveryOptions = Object.entries(DeliveryTypeLabels).map(
    ([value, label]) => ({ value, label })
  );

  const mapOfferToOfferCardInitialValues = (offer: any) => ({
    general_offer_number: offer.general_offer_number ?? "",
    general_status_id: offer.general_status_id ?? "",
    general_profile_crosssection: offer.general_profile_crosssection ?? "",

    general_profile_description: offer.general_profile_description ?? "",
    general_color: offer.general_color ?? "",
    general_packaging: offer.general_packaging ?? "",
    general_article_number: offer.general_article_number ?? "",
    general_tool_number: offer.general_tool_number ?? "",
    general_delivery_type: offer.general_delivery_type ?? "",
    general_order_number: offer.general_order_number ?? "",
  });

  const formik = useFormik({
    initialValues: {
      ...OfferCardInitialValues,
      ...(offerDetails ? mapOfferToOfferCardInitialValues(offerDetails) : {}),
    },
    enableReinitialize: true, //
    onSubmit: () => {},
  });

  // Watch for changes to general_status
  useEffect(() => {
    if (
      offerId &&
      formik.values.general_status !== offerDetails?.general_status
    ) {
      refetchEditableFields();
    }
  }, [formik.values.general_status]);

  return (
    <FormikProvider value={formik}>
      <CardBox label="Angebot" height={CARD_HEIGHT}>
        <Grid container spacing={2}>
          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_offer_number"
              label="Angebotsnummer"
              disabled={!isFieldEditable("general_offer_number")}
            />
          </Grid>
          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_order_number"
              label="Auftragsnummer"
              disabled={!isFieldEditable("general_order_number")}
            />
          </Grid>
          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_profile_description"
              label="Profilbezeichnung"
              disabled={!isFieldEditable("general_profile_description")}
            />
          </Grid>

          {/* Row 2 */}
          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_article_number"
              label="Artikelnummer"
              disabled={!isFieldEditable("general_article_number")}
            />
          </Grid>

          <Grid size={{ xs: 4, md: 4 }}>
            {statusOptions.length > 0 ? (
              <FormSelectSaveField
                name="general_status_id"
                label="Status"
                disabled={!isFieldEditable("general_status_id")}
                options={statusOptions}
                onSaved={refetchEditableFields}
              />
            ) : (
              <Skeleton variant="rectangular" height={56} /> // or any spinner
            )}
          </Grid>

          <Grid size={{ xs: 4, md: 4 }}>
            <FormFloatField
              name="general_profile_crosssection"
              label="Profilquerschnitt [mm²]"
              numeric
              disabled={!isFieldEditable("general_profile_crosssection")}
            />
          </Grid>

          {/* Row 3 */}
          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_tool_number"
              label="Werkzeugnummer"
              disabled={!isFieldEditable("general_tool_number")}
            />
          </Grid>

          <Grid size={{ xs: 4, md: 4 }}></Grid>

          <Grid size={{ xs: 4, md: 4 }}>
            <FormIntField
              name="general_packaging"
              label="Aufmachung [mm]"
              numeric
              disabled={!isFieldEditable("general_packaging")}
            />
          </Grid>

          {/*  */}
          <Grid size={{ xs: 4, md: 4 }}>
            <FormSelectSaveField
              name="general_delivery_type"
              label="Lieferart"
              options={deliveryOptions}
              disabled={!isFieldEditable("general_delivery_type")}
            />
          </Grid>
          <Grid size={{ xs: 4, md: 4 }}></Grid>

          <Grid size={{ xs: 4, md: 4 }}>
            <FormTextField
              name="general_color"
              label="Farbe"
              disabled={!isFieldEditable("general_color")}
            />
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default OfferCard;
