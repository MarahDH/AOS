import { useOfferData } from "./useOfferData";
import { useOfferContext } from "@contexts/OfferProvider";

export const useOfferStatuses = () => {
  const { offerId } = useOfferContext();
  const { data: offerData, ...rest } = useOfferData(offerId!);
  
  return {
    data: offerData?.offer_statuses || [],
    ...rest,
  };
};
