import { useQuery } from "@tanstack/react-query";
import { OffersApi } from "@api/offers";
import { OfferDataResponse } from "@interfaces/Offers.model";

const NEW_OFFER_DEFAULT: OfferDataResponse = {
  editable_fields: ["general_offer_number"],
  offer_statuses: [],
  additives: [],
  raw_materials: [],
  raw_materials_calculated: [],
};

export const useOfferData = (offerId?: number) => {
  return useQuery({
    queryKey: ["offer-data", offerId ?? "new"],
    queryFn: async () => {
      if (!offerId) return NEW_OFFER_DEFAULT;
      return OffersApi.getOfferData(offerId);
    },
    enabled: offerId !== undefined,
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  });
};
