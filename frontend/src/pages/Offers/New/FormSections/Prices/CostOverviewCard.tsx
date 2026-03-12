import CardBox from "@components/CardBox";
import FormFloatField from "@components/FormInputs/FormFloatField";
import Grid from "@mui/material/Grid2";
import Typography from "@mui/material/Typography";
import { Box, useMediaQuery, useTheme } from "@mui/material";
import { FC, useMemo, useEffect } from "react";
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

  // Update formik values when offerDetails changes
  useEffect(() => {
    if (offerDetails) {
      formik.setFieldValue("_pricing_costs_calc_production_time", offerDetails._pricing_costs_calc_production_time ?? "");
      formik.setFieldValue("_pricing_costs_calc_time_costs_quantity", offerDetails._pricing_costs_calc_time_costs_quantity ?? "");
      formik.setFieldValue("_pricing_costs_calc_raw_material_quantity", offerDetails._pricing_costs_calc_raw_material_quantity ?? "");
      formik.setFieldValue("_pricing_costs_calc_raw_material_setup_quantity", offerDetails._pricing_costs_calc_raw_material_setup_quantity ?? "");
      formik.setFieldValue("_pricing_costs_calc_raw_material_quantity_total", offerDetails._pricing_costs_calc_raw_material_quantity_total ?? "");
      formik.setFieldValue("_pricing_costs_calc_raw_material_price_total", offerDetails._pricing_costs_calc_raw_material_price_total ?? "");
      formik.setFieldValue("pricing_costs_calc_price_additional_lfm", offerDetails.pricing_costs_calc_price_additional_lfm ?? "");
      formik.setFieldValue("_pricing_costs_yearly_time_costs_quantity", offerDetails._pricing_costs_yearly_time_costs_quantity ?? "");
      formik.setFieldValue("_pricing_costs_yearly_raw_material_quantity", offerDetails._pricing_costs_yearly_raw_material_quantity ?? "");
      formik.setFieldValue("_pricing_costs_yearly_fixcosts", offerDetails._pricing_costs_yearly_fixcosts ?? "");
      formik.setFieldValue("pricing_costs_calc_price_additional_lfm_desc", offerDetails.pricing_costs_calc_price_additional_lfm_desc ?? "");
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [offerDetails]);

  // Calculate percentages for Jahresmenge fields - ensures they always sum to 100%
  // Uses dynamic precision: more decimal places for smaller percentages
  const yearlyPercentages = useMemo(() => {
    const timeCosts = parseFloat(offerDetails?._pricing_costs_yearly_time_costs_quantity?.toString() || "0") || 0;
    const rawMaterial = parseFloat(offerDetails?._pricing_costs_yearly_raw_material_quantity?.toString() || "0") || 0;
    const fixcosts = parseFloat(offerDetails?._pricing_costs_yearly_fixcosts?.toString() || "0") || 0;
    
    const total = timeCosts + rawMaterial + fixcosts;
    
    if (total === 0) {
      return {
        timeCosts: { value: 0, precision: 1 },
        rawMaterial: { value: 0, precision: 1 },
        fixcosts: { value: 0, precision: 1 },
      };
    }
    
    // Calculate raw percentages
    const timeCostsPercentRaw = (timeCosts / total) * 100;
    const rawMaterialPercentRaw = (rawMaterial / total) * 100;
    const fixcostsPercentRaw = (fixcosts / total) * 100;
    
    // Determine precision based on value size
    const getPrecision = (percent: number): number => {
      if (percent === 0) return 1;
      if (percent < 0.1) return 3; // Use 3 decimals for very small percentages (< 0.1%)
      if (percent < 1) return 2;   // Use 2 decimals for small percentages (< 1%)
      return 1;                     // Use 1 decimal for larger percentages
    };
    
    // Get precision for all three
    const timeCostsPrecision = getPrecision(timeCostsPercentRaw);
    const rawMaterialPrecision = getPrecision(rawMaterialPercentRaw);
    const fixcostsPrecision = getPrecision(fixcostsPercentRaw);
    
    // Round first two percentages
    let timeCostsPercent = Math.round(timeCostsPercentRaw * Math.pow(10, timeCostsPrecision)) / Math.pow(10, timeCostsPrecision);
    let rawMaterialPercent = Math.round(rawMaterialPercentRaw * Math.pow(10, rawMaterialPrecision)) / Math.pow(10, rawMaterialPrecision);
    
    // Calculate fixcosts to ensure sum = exactly 100%
    let fixcostsPercent = 100 - timeCostsPercent - rawMaterialPercent;
    
    // Round fixcosts with appropriate precision
    fixcostsPercent = Math.round(fixcostsPercent * Math.pow(10, fixcostsPrecision)) / Math.pow(10, fixcostsPrecision);
    
    // Final adjustment: if rounding caused sum != 100, adjust the largest value
    const sum = timeCostsPercent + rawMaterialPercent + fixcostsPercent;
    const diff = 100 - sum;
    if (Math.abs(diff) > 0.0001) {
      // Adjust the largest percentage by the difference
      const maxPercent = Math.max(timeCostsPercentRaw, rawMaterialPercentRaw, fixcostsPercentRaw);
      if (maxPercent === timeCostsPercentRaw) {
        timeCostsPercent += diff;
      } else if (maxPercent === rawMaterialPercentRaw) {
        rawMaterialPercent += diff;
      } else {
        fixcostsPercent += diff;
      }
    }
    
    return {
      timeCosts: { value: Math.max(0, timeCostsPercent), precision: timeCostsPrecision },
      rawMaterial: { value: Math.max(0, rawMaterialPercent), precision: rawMaterialPrecision },
      fixcosts: { value: Math.max(0, fixcostsPercent), precision: fixcostsPrecision },
    };
  }, [
    offerDetails?._pricing_costs_yearly_time_costs_quantity,
    offerDetails?._pricing_costs_yearly_raw_material_quantity,
    offerDetails?._pricing_costs_yearly_fixcosts,
  ]);

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

            {jahresmengeRows.map((row, rowIndex) => {
              const field = row[0];
              let percentage: { value: number; precision: number } | null = null;
              
              // Get percentage for the specific field
              if (field.name === "_pricing_costs_yearly_time_costs_quantity") {
                percentage = yearlyPercentages.timeCosts;
              } else if (field.name === "_pricing_costs_yearly_raw_material_quantity") {
                percentage = yearlyPercentages.rawMaterial;
              } else if (field.name === "_pricing_costs_yearly_fixcosts") {
                percentage = yearlyPercentages.fixcosts;
              }
              
              return (
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
                        <Box
                          sx={{
                            display: "flex",
                            alignItems: "flex-end",
                            gap: 1,
                            justifyContent: "space-between",
                          }}
                        >
                          <Box sx={{ flex: 1 }}>
                            <FormFloatField
                              name={field.name}
                              label={field.label}
                              disabled={field!.disabled || !isEditable}
                            />
                          </Box>
                          {percentage !== null && (
                            <Typography
                              variant="body2"
                              sx={{
                                color: "text.secondary",
                                whiteSpace: "nowrap",
                                pb: 0.5,
                                textAlign: "right",
                                fontWeight: "medium",
                              }}
                            >
                              {percentage.value.toFixed(percentage.precision)}%
                            </Typography>
                          )}
                        </Box>
                      </Box>
                    </Grid>
                  ))}
                </Grid>
              );
            })}
          </Grid>
        </Grid>
      </CardBox>
    </FormikProvider>
  );
};

export default CostOverviewCard;
