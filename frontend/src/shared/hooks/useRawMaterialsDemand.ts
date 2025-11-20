import { useMutation, useQueryClient } from "@tanstack/react-query";
import { OfferRawMaterialCalculatedModel } from "@interfaces/RawMaterial.model";
import { OfferRawMaterialCalculatedApi } from "@api/offer-raw-material";
import { useApiSuccessHandler } from "./useApiSuccessHandler";
import { useOfferData } from "./useOfferData";

export const useRawMaterials = (offerId: number) => {
  const queryClient = useQueryClient();
  const { showSuccess } = useApiSuccessHandler();

  // Load raw materials from consolidated data
  const { data: offerData, ...rawMaterialsQuery } = useOfferData(offerId);

  // Update raw material
  const updateMutation = useMutation({
    mutationFn: async ({
      rawMaterialId,
      data,
    }: {
      rawMaterialId: number;
      data: Partial<OfferRawMaterialCalculatedModel>;
    }) => {
      const response =
        await OfferRawMaterialCalculatedApi.updateRawMaterialDemand(
          offerId,
          rawMaterialId,
          data
        );
      showSuccess("Feld erfolgreich gespeichert.");
      return response.data;
    },
    onSuccess: () => {
      // Refresh consolidated data after update
      queryClient.invalidateQueries({ queryKey: ["offer-data", offerId] });
    },
  });

  return {
    data: offerData?.raw_materials_calculated || [],
    ...rawMaterialsQuery,
    updateRawDemanMaterial: updateMutation.mutateAsync,
    updateStatus: updateMutation.status,
    updateError: updateMutation.error,
  };
};
