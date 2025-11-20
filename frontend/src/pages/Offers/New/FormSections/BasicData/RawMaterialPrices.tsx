import CardBox from "@components/CardBox";
import FormInputField from "@components/FormInputField";
import FormInputSaveField from "@components/FormInputSaveField";
import Grid from "@mui/material/Grid2";
import { FormikProvider } from "formik";
import { Typography, Button, Stack, TableRow, TableCell, CircularProgress, Box } from "@mui/material";
import { Table, TableContainer, TableBody, Paper } from "@mui/material";
import RawMaterialTableHead from "./RawMaterialSection/RawMaterialTableHead";
import RawMaterialRow from "./RawMaterialSection/RawMaterialRow";
import { useRawMaterialPricesTable } from "./RawMaterialSection/useRawMaterialPricesTable";
import AdditiveModal from "./Additives/AdditiveModal";
import FormInputFallbackField from "@components/FormInputFallbackField";
import { formatNumberToGerman } from "@utils/formatNumbers";

const RawMaterialPrices = () => {
  const {
    formik,
    baseMaterials,
    selectedMaterial,
    openModal,
    rawMaterialRows,
    setOpenModal,
    updateRawDemanMaterial,
    isFieldEditable,
    handleOpenModal,
    handleChangeMaterial,
    handleUpdateField,
    setRawMaterialRows,
    createEmptyRow,
    handleAddMaterial,
    totalPriceShare,
    totalDemand,
    isLoading,
  } = useRawMaterialPricesTable();

  return (
    <FormikProvider value={formik}>
      <CardBox label="Rohstoffpreise">
        {isLoading ? (
          <Box
            display="flex"
            justifyContent="center"
            alignItems="center"
            minHeight="200px"
          >
            <CircularProgress />
          </Box>
        ) : (
          <>
            <Grid
              container
              spacing={2}
              alignItems="center"
              justifyContent="end"
              mb={2}
            >
          <Grid size={{ xs: 2, md: 1.5 }}>
            <FormInputSaveField
              name="general_raw_material_purchase_discount"
              label="Skonto [%]"
              numeric
              onSaved={() => {}}
              disabled={
                !isFieldEditable("general_raw_material_purchase_discount")
              }
            />
          </Grid>
        </Grid>

        <TableContainer component={Paper}>
          <Table size="small">
            <RawMaterialTableHead />
            <TableBody>
              {rawMaterialRows.map((row, index) => (
                <RawMaterialRow
                  key={index}
                  row={row}
                  rawMaterialRows={rawMaterialRows}
                  baseMaterials={baseMaterials}
                  updateDemand={updateRawDemanMaterial}
                  onChangeMaterial={handleChangeMaterial}
                  onUpdateField={handleUpdateField}
                  setRawMaterialRows={setRawMaterialRows}
                  onOpenModal={handleOpenModal}
                  handleAddMaterial={handleAddMaterial}
                />
              ))}

              {/* Sum row */}
              <TableRow>
                {/* Rohstoff, Typ, Lieferant */}
                <TableCell colSpan={3} />

                {/* Total Demand (Rohstoffbedarf [mm²]) */}
                <TableCell align="left">
                  <Typography fontWeight="bold">
                    Summe {formatNumberToGerman(totalDemand)}
                  </Typography>
                </TableCell>

                {/* Anteil [%], Preisstand, Preis [€/kg], Preis - Sko, Additive, Preis Additive */}
                <TableCell colSpan={5} />

                <TableCell>
                  <Typography fontWeight="bold">Summe</Typography>
                </TableCell>

                {/* Preis (anteilig) */}
                <TableCell align="right">
                  <Stack spacing={0.5} alignItems="flex-end">
                    <FormInputField
                      name="general_raw_material_price_total_calculated"
                      hiddenLabel={true}
                      value={formatNumberToGerman(totalPriceShare)}
                      disabled
                      numeric
                    />
                  </Stack>
                </TableCell>

                {/* Preis - Sko (anteilig) */}
                <TableCell />
              </TableRow>
              {/* ⬇️ Overwritten Total (Summe überschrieben) */}
              <TableRow>
                <TableCell
                  colSpan={10}
                  align="right"
                  sx={{
                    alignContent: "start",
                  }}
                >
                  <Typography fontWeight="bold" pt={1}>
                    Summe (überschrieben)
                  </Typography>
                </TableCell>
                <TableCell align="right">
                  <Stack spacing={0.5}>
                    <FormInputFallbackField
                      name="general_raw_material_price_total_overwritten"
                      label=""
                      fallbackValue={parseFloat(
                        totalPriceShare?.toString() || "0"
                      )}
                      hiddenLabel={true}
                      disabled={
                        !isFieldEditable(
                          "general_raw_material_price_total_overwritten"
                        )
                      }
                    />

                    <Button
                      size="small"
                      disabled={
                        !isFieldEditable(
                          "general_raw_material_price_total_overwritten"
                        )
                      }
                      variant="text"
                      color="secondary"
                      onClick={() =>
                        formik.setFieldValue(
                          "general_raw_material_price_total_overwritten",
                          ""
                        )
                      }
                      sx={{
                        alignSelf: "center",
                        textTransform: "none",
                        pl: 0,
                      }}
                    >
                      Zurücksetzen
                    </Button>
                  </Stack>
                </TableCell>
                <TableCell />
              </TableRow>
            </TableBody>
          </Table>
        </TableContainer>

        {/* Add Row Button */}
        <Grid container justifyContent="flex-end" mt={2}>
          <Grid>
            <Button
              disabled={
                !isFieldEditable("general_raw_material_purchase_discount") ||
                rawMaterialRows.length >= 4
              }
              variant="outlined"
              onClick={() =>
                setRawMaterialRows((prev) =>
                  prev.length < 4 ? [...prev, createEmptyRow()] : prev
                )
              }
            >
              Neue Zeile hinzufügen
            </Button>
          </Grid>
        </Grid>
          </>
        )}
      </CardBox>

      <AdditiveModal
        open={openModal}
        onClose={() => {
          setOpenModal(false);
        }}
        materialName={selectedMaterial?.name || ""}
        initialValues={selectedMaterial?.additives || []}
        selectedMaterial={{
          offer_id: selectedMaterial?.offer_id,
          raw_material_id: selectedMaterial?.raw_material_id,
        }}
      />
    </FormikProvider>
  );
};

export default RawMaterialPrices;
