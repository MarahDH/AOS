import { useOfferData } from "./useOfferData";

export const useEditableFields = (offerId?: number) => {
  const { data: offerData, ...rest } = useOfferData(offerId);
  
  return {
    data: offerData?.editable_fields || [],
    ...rest,
  };
};
