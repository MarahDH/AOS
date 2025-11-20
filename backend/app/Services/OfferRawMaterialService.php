<?php

namespace App\Services;

use App\Repositories\OfferRawMaterialRepository;
use App\Http\Resources\OfferRawMaterialCalculatedResource;
use App\Models\OfferRawMaterialCalculated;

class OfferRawMaterialService
{
    public function __construct(protected OfferRawMaterialRepository $repository) {}

    public function getRawMaterialsCalculated(int $offerId)
    {
        $data = OfferRawMaterialCalculated::where('offer_id', $offerId)->get();
        return OfferRawMaterialCalculatedResource::collection($data);
    }

    public function update(int $offerId, int $rawMaterialId, array $data): OfferRawMaterialCalculatedResource
    {
        return $this->repository->updateRawMaterial($data, $offerId, $rawMaterialId);
    }

    public function updateDemand(int $offerId, int $rawMaterialId, array $data): OfferRawMaterialCalculatedResource
    {
        return $this->repository->updateRawMaterialDemand($data, $offerId, $rawMaterialId);
    }

    public function recalculateShares(int $offerId): void
    {
        $this->repository->recalculateShares($offerId);
    }

    /**
     * Sync prices from raw_materials to offers_raw_materials for a specific offer
     */
    public function syncPricesFromRawMaterials(int $offerId): void
    {
        $this->repository->syncPricesFromRawMaterials($offerId);
    }
}
