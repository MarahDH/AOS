<?php

namespace App\Services;

use App\Models\AdditiveOfferRawMaterial;
use App\Models\Additive;
use App\Repositories\OfferRawMaterialRepository;
use Illuminate\Support\Facades\Log;

class AdditiveOfferRawMaterialService
{
    private $offerRawMaterialRepository;

    public function __construct(OfferRawMaterialRepository $offerRawMaterialRepository)
    {
        $this->offerRawMaterialRepository = $offerRawMaterialRepository;
    }

    public function getAllAdditives()
    {
        return Additive::all();
    }

    public function update(array $data): AdditiveOfferRawMaterial
    {
        $updated = AdditiveOfferRawMaterial::where('offer_id', $data['offer_id'])
            ->where('raw_material_id', $data['raw_material_id'])
            ->where('additives_id', $data['additives_id']);

        $fieldsToUpdate = [];

        if (array_key_exists('price', $data)) {
            $fieldsToUpdate['price'] = $data['price'];
        }

        if (array_key_exists('share', $data)) {
            $fieldsToUpdate['share'] = $data['share'];
        }

        $updated->update($fieldsToUpdate);

        // Update the total price share when additives change
        $this->offerRawMaterialRepository->updateTotalPriceShare($data['offer_id']);

        // Reload the model for response
        return AdditiveOfferRawMaterial::with('additive')
            ->where('offer_id', $data['offer_id'])
            ->where('raw_material_id', $data['raw_material_id'])
            ->where('additives_id', $data['additives_id'])
            ->firstOrFail();
    }
}
