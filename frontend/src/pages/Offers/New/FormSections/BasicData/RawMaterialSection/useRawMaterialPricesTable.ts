import debounce from "lodash.debounce";
import { AdditiveApi } from "@api/additives";
import { RawMaterialRow } from "@interfaces/RawMaterial.model";
import { OfferRawMaterialCalculatedApi } from "@api/offer-raw-material";
import { RawMaterialPricesTableInitialValues } from "@pages/Offers/New/Index";
import { useApiErrorHandler } from "@hooks/useApiErrorHandler";
import { useApiSuccessHandler } from "@hooks/useApiSuccessHandler";
import { useCallback, useEffect, useState, useRef, useMemo } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useEditableFields } from "@hooks/useEditableFields";
import { useFormik } from "formik";
import { useOfferContext } from "@contexts/OfferProvider";
import { useRawMaterials } from "@hooks/useRawMaterialsDemand";
import { useOfferData } from "@hooks/useOfferData";

export const useRawMaterialPricesTable = () => {
  const { showError } = useApiErrorHandler();
  const { showSuccess } = useApiSuccessHandler();
  const queryClient = useQueryClient();

  // Hooks
  const { offerDetails, offerId } = useOfferContext();
  const { updateRawDemanMaterial } = useRawMaterials(offerId!);

  // Permissions
  const { data: editableFields = [] } = useEditableFields(offerId!);

  // Get consolidated data at the hook level
  const { data: offerData, isLoading: isOfferDataLoading } = useOfferData(offerDetails?.id);

  const isFieldEditable = (fieldName: string) =>
    editableFields.includes(fieldName);

  const [baseMaterials, setRawMaterials] = useState<any[]>([]);
  const [rawMaterialRows, setRawMaterialRows] = useState<RawMaterialRow[]>([]);
  const [selectedMaterial, setSelectedMaterial] = useState<any | null>(null);
  const [openModal, setOpenModal] = useState(false);
  // Removed isLoading state - no longer needed since data comes from consolidated query
  
  // AbortController for request cancellation
  const abortControllerRef = useRef<AbortController | null>(null);

  const createEmptyRow = (): RawMaterialRow => ({
    offer_id: offerDetails?.id || 0,
    raw_material_id: 0,
    supplier: "",
    share: 0,
    price_date: "",
    price: 0,
    type: "",
    _additives_concatenated: "",
    _additives_price_sum: 0,
    _price_minus_discount: 0,
    _price_share: 0,
    _price_minus_discount_share: 0,
  });

  // Removed fetchOfferRawMaterials function - data is now handled by useEffect

  // Get raw materials from consolidated data
  useEffect(() => {
    if (offerData?.raw_materials) {
      setRawMaterials(offerData.raw_materials);
    }
  }, [offerData?.raw_materials]);

  const handleAddMaterial = async (newMaterialId: number) => {
    if (!offerDetails?.id) return;

    // Find the base material to prefill fields
    const baseMaterial = baseMaterials.find((m) => m.id === newMaterialId);

    try {
      const createdMaterial =
        await OfferRawMaterialCalculatedApi.createRawMaterial({
          offer_id: offerDetails.id,
          raw_material_id: newMaterialId,
          supplier: baseMaterial?.supplier || "",
          price_date: baseMaterial?.price_date || "",
          // add other fields if needed
        });

      setRawMaterialRows((prev) =>
        prev.map((r) =>
          r.raw_material_id === 0
            ? {
                ...createdMaterial,
                share: r.share,
                supplier: r.supplier || baseMaterial?.supplier || "",
                price_date: r.price_date || baseMaterial?.price_date || "",
                price: r.price,
                type: r.type,
              }
            : r
        )
      );

      showSuccess("Rohstoff erfolgreich hinzugefügt.");
      // Invalidate the consolidated offer-data query to refresh all data
      queryClient.invalidateQueries({ queryKey: ["offer-data", offerDetails.id] });
    } catch (error) {
      showError(error);
    }
  };

  // Removed debouncedFetch function - no longer needed since data comes from consolidated query

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const debouncedUpdate = useCallback(
    debounce(
      async (
        offerId: number,
        rawMaterialId: number,
        field: keyof RawMaterialRow,
        value: any
      ) => {
        try {
          await OfferRawMaterialCalculatedApi.updateRawMaterial(
            offerId,
            rawMaterialId,
            { [field]: value }
          );
          // Invalidate the consolidated offer-data query to refresh all data
          queryClient.invalidateQueries({ queryKey: ["offer-data", offerId] });
          showSuccess("Feld erfolgreich gespeichert.");
        } catch (error) {
          showError(error);
        }
      },
      500
    ),
    [] // Remove debouncedFetch dependency to prevent recreation
  );

  const handleUpdateField = (
    row: RawMaterialRow,
    field: keyof RawMaterialRow,
    value: string | number
  ) => {
    const currentValue = row[field];

    // 🛡️ Only update if the value changed
    if (currentValue === value) {
      return;
    }

    setRawMaterialRows((prev) =>
      prev.map((r) =>
        r.offer_id === row.offer_id && r.raw_material_id === row.raw_material_id
          ? { ...r, [field]: value }
          : r
      )
    );

    if (row.offer_id && row.raw_material_id) {
      debouncedUpdate(row.offer_id, row.raw_material_id, field, value);
    }
  };

  const handleChangeMaterial = async (
    row: RawMaterialRow,
    newMaterialId: number
  ) => {
    try {
      await OfferRawMaterialCalculatedApi.updateRawMaterial(
        row.offer_id,
        row.raw_material_id,
        {
          raw_material_id: newMaterialId,
        }
      );
      // Invalidate the consolidated offer-data query to refresh all data
      queryClient.invalidateQueries({ queryKey: ["offer-data", row.offer_id] });
    } catch (error) {
      showError(error);
    }
  };

  const handleOpenModal = async (row: RawMaterialRow) => {
    try {
      const response = await AdditiveApi.getAdditivesForRawMaterial(
        row.offer_id,
        row.raw_material_id
      );
      const additives = response;
      setSelectedMaterial({
        ...row,
        additives: additives,
      });

      setOpenModal(true);
    } catch (error) {
      showError(error);
    }
  };

  const formik = useFormik<any>({
    initialValues: {
      ...RawMaterialPricesTableInitialValues,
      ...(offerDetails
        ? {
            general_raw_material_price_total_overwritten:
              offerDetails.general_raw_material_price_total_overwritten ?? "",
            general_raw_material_purchase_discount:
              offerDetails.general_raw_material_purchase_discount ?? "",
          }
        : {}),
    },
    enableReinitialize: true,
    onSubmit: () => {},
  });

  // Calculate totals from the actual data source
  const totalPriceShare = useMemo(() => {
    if (!offerData?.raw_materials_calculated) return 0;
    const result = offerData.raw_materials_calculated.reduce(
      (sum, row) => sum + (parseFloat(String(row._price_share)) || 0),
      0
    );
    console.log("Calculating totalPriceShare:", result, "from data:", offerData.raw_materials_calculated);
    return result;
  }, [offerData?.raw_materials_calculated]);

  const totalDemand = useMemo(() => {
    if (!offerData?.raw_materials_calculated) return 0;
    const result = offerData.raw_materials_calculated.reduce(
      (sum, row) => sum + (parseFloat(String(row.absolut_demand)) || 0),
      0
    );
    console.log("Calculating totalDemand:", result, "from data:", offerData.raw_materials_calculated);
    return result;
  }, [offerData?.raw_materials_calculated]);

  // Update formik values when offerData or totalPriceShare changes
  useEffect(() => {
    if (offerData) {
      // Type assertion to handle the new fields we added to the backend
      const data = offerData as any;
      formik.setFieldValue("general_raw_material_purchase_discount", data.general_raw_material_purchase_discount ?? "");
      
      // Always update the overwritten field with the calculated total
      // This ensures it reflects the latest calculated value from raw materials + additives
      if (totalPriceShare !== undefined && totalPriceShare !== null) {
        console.log("Updating formik with totalPriceShare:", totalPriceShare);
        formik.setFieldValue("general_raw_material_price_total_overwritten", totalPriceShare);
      } else {
        console.log("Updating formik with backend value:", data.general_raw_material_price_total_overwritten);
        formik.setFieldValue("general_raw_material_price_total_overwritten", data.general_raw_material_price_total_overwritten ?? "");
      }
    }
  }, [offerData, totalPriceShare]);

  // Update raw material rows when consolidated data changes
  useEffect(() => {
    if (offerData?.raw_materials_calculated) {
      const res = offerData.raw_materials_calculated;
      if (res.length === 0) {
        setRawMaterialRows([createEmptyRow()]);
      } else {
        const filledRows = [...res];
        while (filledRows.length < 4) {
          filledRows.push(createEmptyRow());
        }
        setRawMaterialRows(filledRows);
      }
    }
  }, [offerData?.raw_materials_calculated]);

  // Cleanup function to cancel requests and debounced functions
  useEffect(() => {
    return () => {
      // Cancel any pending requests
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
      // Cancel debounced functions
      debouncedUpdate.cancel();
    };
  }, []); // Remove dependencies to prevent recreation

  return {
    formik,
    baseMaterials,
    rawMaterialRows,
    selectedMaterial,
    openModal,
    updateRawDemanMaterial,
    isFieldEditable,
    setOpenModal,
    handleAddMaterial,
    handleOpenModal,
    setSelectedMaterial,
    handleChangeMaterial,
    handleUpdateField,
    setRawMaterialRows,
    createEmptyRow,
    totalPriceShare,
    totalDemand,
    isLoading: isOfferDataLoading,
  };
};
