import { useOfferContext } from "@contexts/OfferProvider";
import { useAuth } from "@contexts/AuthProvider";
import { Roles } from "@enums/Roles.enum";
import { useOfferStatuses } from "./useOfferStatuses";

export const useDrawingEditable = () => {
  const { offerDetails } = useOfferContext();
  const { user } = useAuth();
  const { data: statuses = [] } = useOfferStatuses();

  const isDrawingEditable = () => {
    const role = user?.role?.name.toLowerCase() as Roles;
    const statusName = offerDetails?.status || null;

    if (!statuses.length) {
      return false; // statuses not loaded yet
    }

    const findStatus = (search: string) => {
      return statuses.find(
        (s: any) => s.name.toLowerCase() === search.toLowerCase()
      );
    };

    const isStatus = (search: string) => {
      const status = findStatus(search);
      return status && status.name === statusName;
    };

    if (role === Roles.ADMIN) {
      return true;
    }

    if (role === Roles.Sales) {
      if (!statusName || isStatus("Vorkalkulation")) {
        return true;
      }
      if (
        isStatus("Angebot") ||
        isStatus("Auftrag") ||
        isStatus("Produziert") ||
        isStatus("Versandt")
      ) {
        return false;
      }
    }

    if (role === Roles.PRODUCTION) {
      if (isStatus("Versandt")) {
        return false;
      }
      return true;
    }

    return false;
  };

  return { isDrawingEditable };
};
