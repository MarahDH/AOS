import { useQuery } from "@tanstack/react-query";
import { OffersApi } from "@api/offers";

export const useOfferData = (offerId?: number) => {
  return useQuery({
    queryKey: ["offer-data", offerId ?? "new"],
    queryFn: async () => {
      if (!offerId) {
        // Return default data for new offers
        return {
          editable_fields: ["general_offer_number"],
          offer_statuses: [],
          additives: [],
          raw_materials: [],
          raw_materials_calculated: [],
        };
      }
      return OffersApi.getOfferData(offerId);
    },
    enabled: offerId !== undefined,
    refetchOnWindowFocus: false,
  });
};
