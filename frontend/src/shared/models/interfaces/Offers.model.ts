export interface OffersModel {
  id: number;
  general_offer_number: string;
  general_customer: string;
  general_profile_description: string;
  general_creation_date: string;
  status: string;
}

/** Full offer object returned by GET /offers/:id and PATCH /offers/:id */
export interface OfferDetailModel {
  id: number;
  general_offer_number: string;
  general_customer: string;
  general_profile_description: string;
  general_creation_date: string;
  general_offer_status_id: number | null;
  status: string;
  created_by_user?: { name: string } | null;
  general_raw_material_price_total_overwritten: number | string | null;
  general_raw_material_purchase_discount: number | string | null;
  [key: string]: unknown;
}

/** Response envelope for create / update offer endpoints */
export interface OfferMutationResponse {
  id: number;
  offer: OfferDetailModel;
}

/** Consolidated data returned by GET /offers/:id/data */
export interface OfferDataResponse {
  editable_fields: string[];
  offer_statuses: { id: number; name: string }[];
  additives: { id: number; name: string; [key: string]: unknown }[];
  raw_materials: { id: number; name: string; type?: string; supplier?: string; price_date?: string; [key: string]: unknown }[];
  raw_materials_calculated: import("./RawMaterial.model").OfferRawMaterialCalculatedModel[];
  general_raw_material_purchase_discount?: number | string | null;
}
