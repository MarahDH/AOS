import { FC } from "react";
import CardBox from "@components/CardBox";
import Grid from "@mui/material/Grid2";
import FormInputSaveField from "@components/FormInputSaveField";
// import { useTheme, useMediaQuery } from "@mui/material";
import { FormikProvider, useFormik } from "formik";
import { useOfferContext } from "@contexts/OfferProvider";
import {
  mapWorkCalculationInitialValues,
  WorkCalculationInitialValues,
} from "../../Index";
import ProfileWeightTripleDisplay from "./ProfileWeightTripleDisplay";
import { usePermissions } from "@hooks/usePermissions";
import ToolCostsCustomerView from "./ToolCostsCustomerView";
import FormIntField from "@components/FormInputs/FormIntField";
import FormFloatField from "@components/FormInputs/FormFloatField";

type FieldConfig = {
  name: string;
  label: string;
  disabled?: boolean;
  numeric?: boolean;
  type?: "int" | "float";
};

const rows: Array<FieldConfig | null>[] = [
  [
    {
      name: "calculation_working_setup_quantity_relative",
      label: "Einstellmenge [%]",
      type: "int",
    },
    {
      name: "calculation_working_extrusion_speed",
      label: "Extrusionsgeschw. [m/min]",
      type: "float",
    },
    {
      name: "_calculation_working_setup_quantity_lfm",
      label: "Einstellmenge [m]",
      disabled: true,
      type: "float",
    },
    {
      name: "_calculation_working_setup_time",
      label: "Einstellzeit [min]",
      disabled: true,
      type: "float",
    },
    {
      name: "calculation_working_annual_requirement_estimated",
      label: "Jahresbedarf (schätz) [m]",
      type: "float",
    },
    null,
  ],
  [
    {
      name: "calculation_working_tool_costs_total",
      label: "Werkzeugkosten gesamt [€]",
      type: "float",
    },
    null,
    // {
    //   name: "calculation_working_tool_costs_customer",
    //   label: "Werkzeugkosten Kunde [€]",
    //   numeric: true,
    // },
    {
      name: "calculation_working_allocation_costs_additional",
      label: "Umlagekosten zzgl. [€]",
      type: "int",
    },
    {
      name: "calculation_working_tool_costs_amortization_years",
      label: "Werkzeugumlager auf Jahre",
      type: "int",
    },
    {
      name: "_calculation_working_allocation_costs_lfm",
      label: "Umlage / m [€]",
      disabled: true,
      type: "float",
    },
    null,
  ],
  [
    {
      name: "general_profile_crosssection",
      label: "Profilquerschnitt [mm²]",
      disabled: true,
      type: "float",
    },
    {
      name: "calculation_working_profile_cross_section_deviation_lower",
      label: "Abweichung nach unten [%]",
      type: "int",
    },
    {
      name: "calculation_working_profile_cross_section_deviation_upper",
      label: "Abweichung nach oben [%]",
      type: "int",
    },
    {
      name: "_calculation_working_density_total",
      label: "Rohstoffdichte gesamt [gr/cm³]",
      disabled: true,
      type: "float",
    },
    null,
    null,
  ],
  [
    // {
    //   name: "calculation_working_setup_quantity_additional",
    //   label: "Einstellmenge Zusatz [%]",
    // },
    {
      name: "calculation_working_hourly_rate",
      label: "Stundensatz [€]",
      type: "int",
    },
    // {
    //   name: "calculation_working_additional_costs",
    //   label: "Zusatzkosten / m [€]",
    //   numeric: true,
    // },
    null,
    null,
    null,
    {
      name: "output_per_hour",
      label: "Ausstoß pro Stunde [kg/h]",
      disabled: true,
      type: "float",
    }
  ],
  [
    {
      name: "calculation_working_commission",
      label: "Provision [%]",
      type: "float",
    },
    { name: "calculation_working_profit", label: "Gewinn [%]", type: "float" },
    {
      name: "calculation_working_discount",
      label: "Skonto [%]",
      type: "float",
    },
  ],
];

const WorkCalculationCard: FC = () => {
  const { offerDetails } = useOfferContext();
  const { canEdit } = usePermissions();
  const isEditable = canEdit("calculation");
  // const theme = useTheme();
  // const isMdUp = useMediaQuery(theme.breakpoints.up("md"));

  const formik = useFormik({
    initialValues: {
      ...WorkCalculationInitialValues,
      ...(offerDetails ? mapWorkCalculationInitialValues(offerDetails) : {}),
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Arbeiten">
        {rows.map((row, rowIndex) => (
          <Grid key={rowIndex} container spacing={1} pt={rowIndex > 0 ? 2 : 0}>
            {row.map((field, colIndex) => (
              <Grid key={colIndex} size={{ xs: 2.3, md: 2.3 }} pb={1}>
                {field ? (
                  (() => {
                    const commonProps = {
                      name: field.name,
                      label: field.label,
                      disabled: field.disabled || !isEditable,
                    };

                    switch (field.type) {
                      case "int":
                        return <FormIntField {...commonProps} />;
                      case "float":
                        return <FormFloatField {...commonProps} />;
                      default:
                        return <FormInputSaveField {...commonProps} />;
                    }
                  })()
                ) : rowIndex === 1 && colIndex === 1 ? (
                  <ToolCostsCustomerView />
                ) : rowIndex === 2 && colIndex === 4 ? (
                  <ProfileWeightTripleDisplay />
                ) : null}
              </Grid>
            ))}
          </Grid>
        ))}
      </CardBox>
    </FormikProvider>
  );
};

export default WorkCalculationCard;
