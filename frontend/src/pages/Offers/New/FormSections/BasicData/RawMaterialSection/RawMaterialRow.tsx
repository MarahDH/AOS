import ConfirmationDialog from "@components/ConfirmationDialog";
import {
  Autocomplete,
  IconButton,
  Stack,
  TableCell,
  TableRow,
  TextField,
  Typography,
} from "@mui/material";
import {
  BaseMaterial,
  OfferRawMaterialCalculatedModel,
  RawMaterialRow as RawMaterialRowType,
} from "@interfaces/RawMaterial.model";
import { Delete, PlaylistAdd } from "@mui/icons-material";
import { formatNumberToGerman, parseGermanNumber } from "@utils/formatNumbers";
import { OfferRawMaterialCalculatedApi } from "@api/offer-raw-material";
import { useApiErrorHandler } from "@hooks/useApiErrorHandler";
import { useApiSuccessHandler } from "@hooks/useApiSuccessHandler";
import { useEditableFields } from "@hooks/useEditableFields";
import { useEffect, useState } from "react";
import { useOfferContext } from "@contexts/OfferProvider";
import { useQueryClient } from "@tanstack/react-query";

interface RawMaterialRowProps {
  row: RawMaterialRowType;
  index?: number;
  baseMaterials: BaseMaterial[];
  onChangeMaterial: (row: RawMaterialRowType, newMaterialId: number) => void;
  onUpdateField: (
    row: RawMaterialRowType,
    field: keyof RawMaterialRowType,
    value: string | number
  ) => void;
  setRawMaterialRows: React.Dispatch<
    React.SetStateAction<RawMaterialRowType[]>
  >;
  onOpenModal: (row: RawMaterialRowType) => void;
  handleAddMaterial: (newMaterialId: number) => void;
  rawMaterialRows: RawMaterialRowType[];
  updateDemand: ({
    rawMaterialId,
    data,
  }: {
    rawMaterialId: number;
    data: Partial<OfferRawMaterialCalculatedModel>;
  }) => void;
}

const RawMaterialRow = ({
  row,
  baseMaterials,
  rawMaterialRows,
  onChangeMaterial,
  onUpdateField,
  setRawMaterialRows,
  onOpenModal,
  updateDemand,
  handleAddMaterial,
}: RawMaterialRowProps) => {
  const { offerDetails, offerId } = useOfferContext();
  const { showError } = useApiErrorHandler();
  const { showSuccess } = useApiSuccessHandler();
  const { data: editableFields = [] } = useEditableFields(offerId!);
  const queryClient = useQueryClient();
  const [confirmOpen, setConfirmOpen] = useState(false);

  const isFieldEditable = (fieldName: string) =>
    editableFields.includes(fieldName);

  // Local input values for formatting
  const [priceInputValue, setPriceInputValue] = useState(
    row.price != null ? formatNumberToGerman(row.price) : ""
  );
  const [totalDemandInputValue, setTotalDemandInputValue] = useState(
    row.absolut_demand != null ? formatNumberToGerman(row.absolut_demand) : ""
  );
  const [supplierInputValue, setSupplierInputValue] = useState(
    row.supplier ?? ""
  );
  const [priceDateInputValue, setPriceDateInputValue] = useState(
    formatPriceDate(row.price_date) ?? ""
  );

  useEffect(() => {
    setPriceInputValue(
      row.price != null ? formatNumberToGerman(row.price) : ""
    );
  }, [row.price]);

  useEffect(() => {
    setTotalDemandInputValue(
      row.absolut_demand != null ? formatNumberToGerman(row.absolut_demand) : ""
    );
  }, [row.absolut_demand]);

  useEffect(() => {
    setSupplierInputValue(row.supplier ?? "");
  }, [row.supplier]);

  useEffect(() => {
    setPriceDateInputValue(formatPriceDate(row.price_date) ?? "");
  }, [row.price_date]);

  const handleConfirmDelete = async () => {
    if (!row.raw_material_id || !offerDetails?.id) return;

    try {
      await OfferRawMaterialCalculatedApi.deleteRawMaterial(
        offerDetails.id,
        row.raw_material_id
      );
      showSuccess("Rohstoff erfolgreich gelöscht.");
      
      // Invalidate the consolidated offer-data query to refresh all data
      // This will trigger a refetch and update both rawMaterialRows and totals
      queryClient.invalidateQueries({ queryKey: ["offer-data", offerDetails.id] });
      
      setConfirmOpen(false);
    } catch (error) {
      showError(error);
      setConfirmOpen(false);
    }
  };

  const usedMaterialIds = rawMaterialRows.map((r) => r.raw_material_id);
  const availableMaterials = baseMaterials.filter(
    (material) => !usedMaterialIds.includes(material.id)
  );

  return (
    <TableRow>
      <TableCell>
        <Autocomplete
          options={availableMaterials}
          clearIcon={null}
          getOptionLabel={(option) =>
            `${option.name}${option.type ? ` (${option.type})` : ""}`
          }
          value={
            baseMaterials.find((m) => m.id === row.raw_material_id) || null
          }
          onChange={(_, newValue) => {
            if (!row.raw_material_id && newValue) {
              handleAddMaterial(newValue.id);
            } else if (newValue) {
              onChangeMaterial(row, newValue.id);
            }
          }}
          isOptionEqualToValue={(option, value) => option.id === value.id}
          renderInput={(params) => (
            <TextField
              {...params}
              variant="standard"
              label="Rohstoff"
              disabled={!isFieldEditable("raw_material_id")}
            />
          )}
        />
      </TableCell>

      <TableCell>
        <Typography>{row.type || "-"}</Typography>
      </TableCell>

      <TableCell>
        <TextField
          fullWidth
          disabled={!isFieldEditable("supplier")}
          variant="standard"
          value={supplierInputValue}
          onChange={(e) => setSupplierInputValue(e.target.value)}
          onBlur={() => {
            updateRowField(
              setRawMaterialRows,
              row,
              "supplier",
              supplierInputValue
            );
            onUpdateField(row, "supplier", supplierInputValue);
          }}
        />
      </TableCell>

      {/* Rohstoffbedarf [mm²] absolut_demand*/}
      <TableCell>
        <TextField
          fullWidth
          disabled={!isFieldEditable("absolut_demand")}
          variant="standard"
          value={totalDemandInputValue}
          onChange={(e) => {
            const val = e.target.value.replace(/[^0-9,.-]/g, "");
            setTotalDemandInputValue(val);
          }}
          onBlur={async () => {
            const parsed = parseGermanNumber(totalDemandInputValue);
            if (parsed === null) {
              return;
            }

            if (parsed === row.absolut_demand) {
              return;
            }

            await updateDemand({
              rawMaterialId: row.raw_material_id,
              data: { absolut_demand: parsed },
            });

            updateRowField(setRawMaterialRows, row, "absolut_demand", parsed);
            setTotalDemandInputValue(formatNumberToGerman(parsed));
          }}
        />
      </TableCell>

      {/* Anteil [%] */}

      <TableCell>
        <Typography>
          {row.share != null ? formatNumberToGerman(row.share) : "-"}
        </Typography>
      </TableCell>

      {/* Preisstand */}
      <TableCell>
        <TextField
          type="month"
          variant="standard"
          disabled={!isFieldEditable("price_date")}
          value={priceDateInputValue}
          onChange={(e) => setPriceDateInputValue(e.target.value)}
          onBlur={() => {
            const value = `${priceDateInputValue}-01`;
            updateRowField(setRawMaterialRows, row, "price_date", value);
            onUpdateField(row, "price_date", value);
          }}
        />
      </TableCell>

      {/* Preis [€] /kg */}
      <TableCell>
        <TextField
          fullWidth
          disabled={!isFieldEditable("price")}
          variant="standard"
          value={priceInputValue}
          onChange={(e) => {
            const val = e.target.value.replace(/[^0-9,.-]/g, "");
            setPriceInputValue(val);
          }}
          onBlur={() => {
            const parsed = parseGermanNumber(priceInputValue);
            if (parsed !== null) {
              updateRowField(setRawMaterialRows, row, "price", parsed);
              onUpdateField(row, "price", parsed);
              setPriceInputValue(formatNumberToGerman(parsed));
            }
          }}
        />
      </TableCell>

      {/* Preis - Sko [€] */}
      <TableCell>
        <Typography>
          {row._price_minus_discount != null
            ? formatNumberToGerman(row._price_minus_discount)
            : "-"}
        </Typography>
      </TableCell>

      <TableCell>
        <Typography>{row._additives_concatenated || "-"}</Typography>
      </TableCell>

      <TableCell>
        <Typography>
          {row._additives_price_sum != null
            ? formatNumberToGerman(row._additives_price_sum)
            : "-"}
        </Typography>
      </TableCell>

      <TableCell>
        <Typography>
          {row._price_share != null
            ? formatNumberToGerman(row._price_share)
            : "-"}
        </Typography>
      </TableCell>

      <TableCell>
        <Typography>
          {row._price_minus_discount_share != null
            ? formatNumberToGerman(row._price_minus_discount_share)
            : "-"}
        </Typography>
      </TableCell>

      <TableCell>
        <Stack direction="row" spacing={1}>
          <IconButton onClick={() => onOpenModal(row)} size="small">
            <PlaylistAdd />
          </IconButton>
          <IconButton
            onClick={() => setConfirmOpen(true)}
            size="small"
            color="error"
            disabled={!isFieldEditable("raw_material_id")}
          >
            <Delete />
          </IconButton>
        </Stack>
      </TableCell>

      <ConfirmationDialog
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleConfirmDelete}
        title="Rohstoff löschen"
        message="Möchten Sie diesen Rohstoff wirklich löschen?"
        confirmText="Löschen"
        cancelText="Abbrechen"
        danger
      />
    </TableRow>
  );
};

const updateRowField = (
  setRows: React.Dispatch<React.SetStateAction<RawMaterialRowType[]>>,
  row: RawMaterialRowType,
  field: keyof RawMaterialRowType,
  value: any
) => {
  setRows((prev) =>
    prev.map((r) =>
      r.offer_id === row.offer_id && r.raw_material_id === row.raw_material_id
        ? { ...r, [field]: value }
        : r
    )
  );
};

const formatPriceDate = (priceDate?: string): string => {
  if (!priceDate) return "";
  if (priceDate.includes(".")) {
    const parts = priceDate.split(".");
    if (parts.length === 3) return `${parts[2]}-${parts[1]}`;
  } else if (priceDate.includes("-")) {
    return priceDate.slice(0, 7);
  }
  return "";
};

export default RawMaterialRow;
