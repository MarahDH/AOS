import CardBox from "@components/CardBox";
import FormFloatField from "@components/FormInputs/FormFloatField";
import Grid from "@mui/material/Grid2";
import Typography from "@mui/material/Typography";
import { Box, useMediaQuery, useTheme } from "@mui/material";
import { FC } from "react";
import { FormikProvider, useFormik } from "formik";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";
import FormTextField from "@components/FormInputs/FormTextField";

type FieldConfig = {
  name: string;
  label: string;
  disabled?: boolean;
  numeric?: boolean;
  type?: "int" | "float" | "string";
};

// Left side fields (Kalkulationsmenge)
const kalkulationsmengeRows: Array<FieldConfig | null>[] = [
  [
    {
      name: "_pricing_costs_calc_production_time",
      label: "Produktionszeit [h]",
      disabled: true,
      type: "float",
    },
    null,
    null,
    {
      name: "_pricing_costs_calc_time_costs_quantity",
      label: "Zeitkosten gesamt [€]",
      disabled: true,
      type: "float",
    },
  ],
  [
    {
      name: "_pricing_costs_calc_raw_material_quantity",
      label: "Rohstoffmenge [kg]",
      disabled: true,
      type: "float",
    },
    {
      name: "_pricing_costs_calc_raw_material_setup_quantity",
      label: "Einstellmenge [kg]",
      disabled: true,
      type: "float",
    },
    {
      name: "_pricing_costs_calc_raw_material_quantity_total",
      label: "Rohstoffmenge gesamt [kg]",
      disabled: true,
      type: "float",
    },
    {
      name: "_pricing_costs_calc_raw_material_price_total",
      label: "Rohstoffpreis gesamt [€]",
      disabled: true,
      type: "float",
    },
  ],
  [null, null, null],
  [
    {
      name: "pricing_costs_calc_price_additional_lfm",
      label: "Zusatzpreis / m [€]",
      disabled: false,
      type: "float",
    },
    {
      name: "pricing_costs_calc_price_additional_lfm_desc",
      label: "Zusatzpreis Beschreibung",
      disabled: false,
      type: "string",
    },
    null,
  ],
];

// Right side fields (Jahresmenge)
const jahresmengeRows = [
  [
    {
      name: "_pricing_costs_yearly_time_costs_quantity",
      label: "Zeiteinsatz [€]",
      disabled: true,
      type: "float",
    },
  ],
  [
    {
      name: "_pricing_costs_yearly_raw_material_quantity",
      label: "Rohstoffeinsatz [€]",
      disabled: true,
      type: "float",
    },
  ],
  [
    {
      name: "_pricing_costs_yearly_fixcosts",
      label: "Fixkosten [€]",
      disabled: true,
      type: "float",
    },
  ],
];

const CostOverviewCard: FC = () => {
  const { offerDetails } = useOfferContext();
  const theme = useTheme();
  const isMdDown = useMediaQuery(theme.breakpoints.down("md"));

  // Permissions
  const { canEdit } = usePermissions();
  const isEditable = canEdit("prices");

  const formik = useFormik({
    initialValues: {
      _pricing_costs_calc_production_time:
        offerDetails?._pricing_costs_calc_production_time ?? "",

      _pricing_costs_calc_time_costs_quantity:
        offerDetails?._pricing_costs_calc_time_costs_quantity ?? "",

      _pricing_costs_calc_raw_material_quantity:
        offerDetails?._pricing_costs_calc_raw_material_quantity ?? "",

      _pricing_costs_calc_raw_material_setup_quantity:
        offerDetails?._pricing_costs_calc_raw_material_setup_quantity ?? "",

      _pricing_costs_calc_raw_material_quantity_total:
        offerDetails?._pricing_costs_calc_raw_material_quantity_total ?? "",

      _pricing_costs_calc_raw_material_price_total:
        offerDetails?._pricing_costs_calc_raw_material_price_total ?? "",

      pricing_costs_calc_price_additional_lfm:
        offerDetails?.pricing_costs_calc_price_additional_lfm ?? "",

      _pricing_costs_yearly_time_costs_quantity:
        offerDetails?._pricing_costs_yearly_time_costs_quantity ?? "",

      _pricing_costs_yearly_raw_material_quantity:
        offerDetails?._pricing_costs_yearly_raw_material_quantity ?? "",

      _pricing_costs_yearly_fixcosts:
        offerDetails?._pricing_costs_yearly_fixcosts ?? "",

      pricing_costs_calc_price_additional_lfm_desc:
        offerDetails?.pricing_costs_calc_price_additional_lfm_desc ?? "",
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  return (
    <FormikProvider value={formik}>
      <CardBox label="Kosten">
        <Grid container spacing={8}>
          {/* Left Column (Kalkulationsmenge) */}
          <Grid size={{ xs: 12, md: 8 }}>
            <Typography
              variant="subtitle1"
              fontWeight="bold"
              textAlign="center"
              mb={4}
            >
              Kalkulationsmenge
            </Typography>

            {kalkulationsmengeRows.map((row, rowIndex) => {
              const isEmptyRow = row.every((field) => field === null);

              if (isMdDown) {
                return (
                  <Grid container spacing={2} key={rowIndex}>
                    {row
                      .filter((field) => field !== null)
                      .map((field, colIndex) => (
                        <Grid size={12} key={colIndex}>
                          <Box mb={2}>
                            {field.type === "string" ? (
                              <FormTextField
                                name={field.name}
                                label={field.label}
                                disabled={field.disabled || !isEditable}
                              />
                            ) : (
                              <FormFloatField
                                name={field.name}
                                label={field.label}
                                disabled={field.disabled || !isEditable}
                              />
                            )}
                          </Box>
                        </Grid>
                      ))}
                  </Grid>
                );
              }

              return (
                <Grid
                  container
                  spacing={8}
                  pt={rowIndex > 0 ? 1 : 0}
                  key={rowIndex}
                >
                  {isEmptyRow ? (
                    <Grid size={12}>
                      <Box sx={{ height: 50 }} />
                    </Grid>
                  ) : (
                    row.map((field, colIndex) => (
                      <Grid key={colIndex} size={{ xs: 12, md: 3 }}>
                        {field ? (
                          field.type === "string" ? (
                            <FormTextField
                              name={field.name}
                              label={field.label}
                              disabled={field.disabled || !isEditable}
                            />
                          ) : (
                            <FormFloatField
                              name={field.name}
                              label={field.label}
                              disabled={field.disabled || !isEditable}
                            />
                          )
                        ) : null}
                      </Grid>
                    ))
                  )}
                </Grid>
              );
            })}
          </Grid>

          {/* Right Column (Jahresmenge) */}
          <Grid size={{ xs: 12, md: 4 }}>
            <Typography
              variant="subtitle1"
              fontWeight="bold"
              textAlign="center"
              mb={4}
            >
              Jahresmenge
            </Typography>

            {jahresmengeRows.map((row, rowIndex) => (
              <Grid
                container
                spacing={8}
                pt={rowIndex > 0 ? 1 : 0}
                key={rowIndex}
                justifyContent="center"
              >
                {row.map((field, colIndex) => (
                  <Grid size={isMdDown ? 12 : 5} key={colIndex}>
                    <Box mb={isMdDown ? 2 : 0}>
                      <FormFloatField
                        name={field.name}
                        label={field.label}
                        disabled={field!.disabled || !isEditable}
                      />
                    </Box>
                  </Grid>
                ))}
              </Grid>
            ))}
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default CostOverviewCard;
