import { OffersApi } from "@api/offers";
import { useOfferContext } from "@contexts/OfferProvider";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { NavigateFunction } from "react-router-dom";

// Saving these fields changes which fields are editable (backend rebuilds editable_fields),
// so the full offer-data query must be invalidated to reflect the new permissions.
const FIELDS_REQUIRING_OFFER_DATA_REFRESH = ["general_offer_status_id"];

export const useSaveFieldMutation = (navigate?: NavigateFunction) => {
  const { offerId, offerDetails, setOfferData, setOfferId } = useOfferContext();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ name, value }: { name: string; value: unknown }) => {
      if (!offerId) {
        const res = await OffersApi.createOffer({ field: name, value });
        return { isNew: true as const, id: res.id, offer: res.offer };
      }
      const res = await OffersApi.UpdateOffer(offerId, { field: name, value });
      return { isNew: false as const, id: offerId, offer: res.offer };
    },
    onMutate: ({ name, value }) => {
      if (!offerId) return;
      // Apply change immediately so the UI feels instant
      const previous = offerDetails;
      setOfferData({ ...offerDetails, [name]: value } as typeof offerDetails);
      return { previous };
    },
    onSuccess: ({ isNew, id, offer }, { name }) => {
      if (isNew) {
        setOfferId(id);
        setOfferData(offer);
        navigate?.(`/angebote/${id}`);
      } else {
        // Sync server response (may include computed fields) to both sources
        setOfferData(offer);
        queryClient.setQueryData(["offer", id], offer);
        // Only do a full offer-data refresh when editable_fields could have changed
        if (FIELDS_REQUIRING_OFFER_DATA_REFRESH.includes(name)) {
          queryClient.invalidateQueries({ queryKey: ["offer-data", id] });
        }
      }
    },
    onError: (_err, _vars, ctx) => {
      // Roll back the optimistic update on failure
      if (ctx?.previous !== undefined) {
        setOfferData(ctx.previous);
      }
    },
  });
};
