import { Box, Typography } from "@mui/material";
import FormFloatField from "@components/FormInputs/FormFloatField";
import { usePermissions } from "@hooks/usePermissions";
import { useOfferContext } from "@contexts/OfferProvider";

const ToolCostsCustomerView = () => {
  const { offerDetails } = useOfferContext();
  const { canEdit } = usePermissions();
  const isEditable = canEdit("calculation");

  const customerCosts = Number(
    offerDetails.calculation_working_tool_costs_customer ?? 0
  );
  const totalCosts = Number(
    offerDetails.calculation_working_tool_costs_total ?? 1
  );
  const percent =
    totalCosts && customerCosts
      ? `(${((customerCosts / totalCosts) * 100).toFixed(2)} %)`
      : "(0.00 %)";

  return (
    <Box sx={{ position: "relative", width: "100%" }}>
      <FormFloatField
        name="calculation_working_tool_costs_customer"
        label="Werkzeugkosten Kunde [€]"
        disabled={!isEditable}
        sx={{
          pr: "60px",
        }}
      />
      <Typography
        variant="body2"
        sx={{
          position: "absolute",
          right: 12,
          top: "50%",
          transform: "translateY(-50%)",
          color: "gray",
          pointerEvents: "none",
        }}
      >
        {percent}
      </Typography>
    </Box>
  );
};

export default ToolCostsCustomerView;
