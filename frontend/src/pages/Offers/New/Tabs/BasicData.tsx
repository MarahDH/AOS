import CardBox from "@components/CardBox";
import CustomerCard from "../FormSections/BasicData/CustomerCard";
import FormTextField from "@components/FormInputs/FormTextField";
import Grid from "@mui/material/Grid2";
import HistoryCard from "../FormSections/BasicData/HistoryCard";
import OfferCard from "../FormSections/BasicData/OfferCard";
import RawMaterialPricesTable from "../FormSections/BasicData/RawMaterialPrices";
import { FormikProvider, useFormik } from "formik";
import { FunctionComponent } from "react";
import { useCalculatedValues } from "@hooks/useCalculatedValues";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

type BasicDataTabProps = object;

const BasicDataTab: FunctionComponent<BasicDataTabProps> = () => {
  useCalculatedValues();

  const { offerDetails } = useOfferContext();
  const { canEdit, canView } = usePermissions();

  const formik = useFormik({
    initialValues: {
      general_comments: offerDetails.general_comments ?? "",
    },
    onSubmit: () => {},
  });

  return (
    <>
      {canView("basic_data") && (
        <>
          <Grid container spacing={1}>
            <Grid size={{ xs: 12, md: 5 }}>
              <OfferCard />
            </Grid>
            <Grid size={{ xs: 12, md: 5 }}>
              <CustomerCard />
            </Grid>
            <Grid size={{ xs: 12, md: 2 }}>
              {offerDetails.general_creation_date &&
              offerDetails.created_by_user ? (
                <HistoryCard />
              ) : null}
            </Grid>
          </Grid>

          {/* /* Raw Materials */}
          <Grid container spacing={1}>
            <Grid size={{ xs: 12, md: 12 }}>
              <RawMaterialPricesTable />
            </Grid>
          </Grid>

          {/* /* Bemerkungen */}
          <Grid container spacing={1}>
            <Grid size={{ xs: 12, md: 12 }}>
              <FormikProvider value={formik}>
                <CardBox label="">
                  <FormTextField
                    name="general_comments"
                    label="Bemerkungen"
                    multiline
                    rows={5}
                    disabled={!canEdit("basic_data")}
                  />
                </CardBox>
              </FormikProvider>
            </Grid>
          </Grid>
        </>
      )}
    </>
  );
};

export default BasicDataTab;
