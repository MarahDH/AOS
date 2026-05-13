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

  const { offerDetails, offerId } = useOfferContext();
  const { updateRawDemanMaterial } = useRawMaterials(offerId!);

  const { data: editableFields = [] } = useEditableFields(offerId!);

  const { data: offerData, isLoading: isOfferDataLoading } = useOfferData(offerDetails?.id as number | undefined);

  const isFieldEditable = (fieldName: string) => editableFields.includes(fieldName);

  const [baseMaterials, setRawMaterials] = useState<any[]>([]);
  const [rawMaterialRows, setRawMaterialRows] = useState<RawMaterialRow[]>([]);
  const [selectedMaterial, setSelectedMaterial] = useState<any | null>(null);
  const [openModal, setOpenModal] = useState(false);

  const abortControllerRef = useRef<AbortController | null>(null);

  const createEmptyRow = (): RawMaterialRow => ({
    offer_id: (offerDetails?.id as number) || 0,
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

  useEffect(() => {
    if (offerData?.raw_materials) {
      setRawMaterials(offerData.raw_materials);
    }
  }, [offerData?.raw_materials]);

  const handleAddMaterial = useCallback(
    async (newMaterialId: number) => {
      if (!offerDetails?.id) return;

      const baseMaterial = baseMaterials.find((m) => m.id === newMaterialId);

      try {
        const createdMaterial = await OfferRawMaterialCalculatedApi.createRawMaterial({
          offer_id: offerDetails.id as number,
          raw_material_id: newMaterialId,
          supplier: baseMaterial?.supplier || "",
          price_date: baseMaterial?.price_date || "",
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
        queryClient.invalidateQueries({ queryKey: ["offer-data", offerDetails.id] });
      } catch (error) {
        showError(error);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [offerDetails?.id, baseMaterials, queryClient]
  );

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
          await OfferRawMaterialCalculatedApi.updateRawMaterial(offerId, rawMaterialId, {
            [field]: value,
          });
          queryClient.invalidateQueries({ queryKey: ["offer-data", offerId] });
          showSuccess("Feld erfolgreich gespeichert.");
        } catch (error) {
          showError(error);
        }
      },
      500
    ),
    []
  );

  const handleUpdateField = useCallback(
    (row: RawMaterialRow, field: keyof RawMaterialRow, value: string | number) => {
      if (row[field] === value) return;

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
    },
    [debouncedUpdate]
  );

  const handleChangeMaterial = useCallback(
    async (row: RawMaterialRow, newMaterialId: number) => {
      try {
        await OfferRawMaterialCalculatedApi.updateRawMaterial(row.offer_id, row.raw_material_id, {
          raw_material_id: newMaterialId,
        });
        queryClient.invalidateQueries({ queryKey: ["offer-data", row.offer_id] });
      } catch (error) {
        showError(error);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [queryClient]
  );

  const handleOpenModal = useCallback(
    async (row: RawMaterialRow) => {
      if (!row.raw_material_id) return;

      try {
        const cacheKey = ["additives-for-rm", row.offer_id, row.raw_material_id] as const;

        // Use cached result when available — avoids a network round-trip on repeated opens
        let additives = queryClient.getQueryData<any[]>(cacheKey);
        if (!additives) {
          additives = await AdditiveApi.getAdditivesForRawMaterial(row.offer_id, row.raw_material_id);
          queryClient.setQueryData(cacheKey, additives);
        }

        setSelectedMaterial({ ...row, additives });
        setOpenModal(true);
      } catch (error) {
        showError(error);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [queryClient]
  );

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

  const totalPriceShare = useMemo(() => {
    if (!offerData?.raw_materials_calculated) return 0;
    return offerData.raw_materials_calculated.reduce(
      (sum, row) => sum + (parseFloat(String(row._price_share)) || 0),
      0
    );
  }, [offerData?.raw_materials_calculated]);

  const totalDemand = useMemo(() => {
    if (!offerData?.raw_materials_calculated) return 0;
    return offerData.raw_materials_calculated.reduce(
      (sum, row) => sum + (parseFloat(String(row.absolut_demand)) || 0),
      0
    );
  }, [offerData?.raw_materials_calculated]);

  useEffect(() => {
    if (offerData) {
      const data = offerData as any;
      formik.setFieldValue("general_raw_material_purchase_discount", data.general_raw_material_purchase_discount ?? "");

      const savedValue = offerDetails?.general_raw_material_price_total_overwritten;
      if (savedValue !== null && savedValue !== undefined && savedValue !== "") {
        formik.setFieldValue("general_raw_material_price_total_overwritten", savedValue);
      } else if (totalPriceShare !== undefined && totalPriceShare !== null) {
        formik.setFieldValue("general_raw_material_price_total_overwritten", totalPriceShare);
      } else {
        formik.setFieldValue("general_raw_material_price_total_overwritten", "");
      }
    }
  }, [offerData, totalPriceShare, offerDetails]);

  useEffect(() => {
    if (offerData?.raw_materials_calculated) {
      const res = offerData.raw_materials_calculated;
      if (res.length === 0) {
        setRawMaterialRows([createEmptyRow()]);
      } else {
        const filledRows: RawMaterialRow[] = [...res];
        while (filledRows.length < 4) {
          filledRows.push(createEmptyRow());
        }
        setRawMaterialRows(filledRows);
      }
    }
  }, [offerData?.raw_materials_calculated]);

  useEffect(() => {
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
      debouncedUpdate.cancel();
    };
  }, []);

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
