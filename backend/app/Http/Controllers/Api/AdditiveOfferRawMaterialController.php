<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Collections\AdditiveOfferRawMaterialCollection;
use App\Http\Requests\Additives\AttachAdditiveRequest;
use App\Http\Requests\Additives\GetAdditivesForRawMaterialRequest;
use App\Http\Requests\Additives\UpdateAdditiveOfferRequest;
use App\Http\Resources\AdditiveOfferRawMaterialResource;
use App\Http\Resources\ApiResponse;
use App\Models\Additive;
use App\Models\AdditiveOfferRawMaterial;
use App\Services\AdditiveOfferRawMaterialService;
use App\Repositories\OfferRawMaterialRepository;

class AdditiveOfferRawMaterialController extends BaseController
{

    public function __construct(
        private AdditiveOfferRawMaterialService $service,
        private OfferRawMaterialRepository $offerRawMaterialRepository
    ) {}

    public function getAdditivesForRawMaterial(GetAdditivesForRawMaterialRequest $request)
    {

        $additives = AdditiveOfferRawMaterial::with('additive')
            ->where('offer_id', $request->offer_id)
            ->where('raw_material_id', $request->raw_material_id)
            ->get();


        return ApiResponse::success(new AdditiveOfferRawMaterialCollection($additives));
    }


    public function store(AttachAdditiveRequest $request)
    {
        // Fetch base additive info first
        $additive = Additive::findOrFail($request->additives_id);

        // Create or fetch the existing additive-offer record
        $additiveOffer = AdditiveOfferRawMaterial::firstOrCreate(
            [
                'offer_id' => $request->offer_id,
                'raw_material_id' => $request->raw_material_id,
                'additives_id' => $request->additives_id,
            ],
            [
                'share' => 0,
                'price' => $additive->price,
            ]
        );

        $additiveOffer->load('additive');

        // Update the total price share when additives are added
        $this->offerRawMaterialRepository->updateTotalPriceShare($request->offer_id);

        return ApiResponse::success(
            new AdditiveOfferRawMaterialResource($additiveOffer),
            'Additive hinzugefügt.'
        );
    }



    public function update(UpdateAdditiveOfferRequest $request)
    {
        $additiveOffer = $this->service->update($request->validated());

        return ApiResponse::success(new AdditiveOfferRawMaterialResource($additiveOffer), 'Additive-Daten aktualisiert.');
    }

    public function destroy(AttachAdditiveRequest $request)
    {
        AdditiveOfferRawMaterial::where('offer_id', $request->offer_id)
            ->where('raw_material_id', $request->raw_material_id)
            ->where('additives_id', $request->additives_id)
            ->delete();

        // Update the total price share when additives are deleted
        $this->offerRawMaterialRepository->updateTotalPriceShare($request->offer_id);

        return ApiResponse::success(null, 'Additive deleted successfully');
    }
}
