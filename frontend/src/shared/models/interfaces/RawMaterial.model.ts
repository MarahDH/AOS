export interface RawMaterialModel {
  id: number;
  name: string;
  type: string;
  price_per_kg: number;
  price_date: string;
}

export interface OfferRawMaterialCalculatedModel {
  offer_id: number;
  raw_material_id: number;
  absolut_demand: number;
  share: number;
  supplier?: string;
  name?: string;
  price: number;
  price_date: string;
  _additives_concatenated?: string;
  _additives_price_sum?: number;
  _price_minus_discount?: number;
  _price_share?: number;
  _price_minus_discount_share?: number;
  density: number;
  type: string;
  id: number;
}

export interface RawMaterialRow {
  offer_id: number;
  raw_material_id: number;
  name?: string;
  type?: string;
  supplier?: string;
  share: number;
  price?: number;
  price_date?: string;
  absolut_demand?: number;
  // Present on server-returned rows, absent on locally-constructed empty rows
  id?: number;
  density?: number;
  _additives_concatenated?: string;
  _additives_price_sum?: number;
  price_total?: number;
  _price_minus_discount?: number;
  _price_share?: number;
  _price_minus_discount_share?: number;
}
export interface BaseMaterial {
  id: number;
  name: string;
  type: string;
  price_per_kg: number;
  price_date: string;
  density: number;
  additives: string[];
  additives_price_sum: number;
  price_minus_discount: number;
  price_share: number;
  price_minus_discount_share: number;
  supplier: string;
}
