import { OffersModel } from "@interfaces/Offers.model";
export type Column<T> = {
  label: string;
  render: (row: T) => React.ReactNode;
  sortKey?: keyof T;
};
export const OfferColumns: Column<OffersModel>[] = [
  { label: "Angebotsnummer", render: (row) => row.general_offer_number, sortKey: "general_offer_number" },
  { label: "Status", render: (row) => row.status, sortKey: "status" },

  { label: "Auftraggeber", render: (row) => row.general_customer, sortKey: "general_customer" },
  {
    label: "Profilbezeichnung",
    render: (row) => row.general_profile_description,
    sortKey: "general_profile_description",
  },
  { label: "Erstelldatum", render: (row) => row.general_creation_date, sortKey: "general_creation_date" },
];
