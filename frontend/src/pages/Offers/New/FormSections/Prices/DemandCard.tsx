import { FC } from "react";
import CardBox from "@components/CardBox";
import Grid from "@mui/material/Grid2";
import FormInputSaveField from "@components/FormInputSaveField";
import { useOfferContext } from "@contexts/OfferProvider";
import { FormikProvider, useFormik } from "formik";
import { usePermissions } from "@hooks/usePermissions";
import FormFloatField from "@components/FormInputs/FormFloatField";

const DemandCard: FC = () => {
  const { offerDetails } = useOfferContext();
  // Permissions
  const { canEdit } = usePermissions();
  const isEditable = canEdit("prices");

  const formik = useFormik({
    initialValues: {
      calculation_working_annual_requirement_estimated:
        offerDetails.calculation_working_annual_requirement_estimated ?? "",
      _pricing_requirement_annual_sales:
        offerDetails._pricing_requirement_annual_sales != null
          ? Math.round(
              Number(offerDetails._pricing_requirement_annual_sales) * 100
            ) / 100
          : "",
    },

    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Bedarf">
        <Grid container spacing={5}>
          <Grid size={{ xs: 12, md: 2 }}>
            <FormFloatField
              name="calculation_working_annual_requirement_estimated"
              label="Jahresbedarf, geschätzt [m]"
              disabled={!isEditable}
            />
          </Grid>
          <Grid size={{ xs: 12, md: 2 }}>
            <FormInputSaveField
              name="_pricing_requirement_annual_sales"
              label="Jahresumsatz, geschätzt [€]"
              disabled
            />
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default DemandCard;
