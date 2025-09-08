<?php

namespace App\Repositories;

use App\Http\Resources\OfferRawMaterialCalculatedResource;
use App\Models\OfferRawMaterial;
use App\Models\OfferRawMaterialCalculated;
use Illuminate\Support\Facades\DB;

class OfferRawMaterialRepository
{
    public function updateRawMaterial(array $data, int $offerId, int $rawMaterialId): OfferRawMaterialCalculatedResource
    {
        $current = OfferRawMaterial::where('offer_id', $offerId)
            ->where('raw_material_id', $rawMaterialId)
            ->firstOrFail();

        // ✅ ADD THIS:
        if (empty($data)) {
            throw new \Exception('No data provided to update.');
        }

        if (empty($data['raw_material_id']) || $data['raw_material_id'] == $rawMaterialId) {
            OfferRawMaterial::where('offer_id', $offerId)
                ->where('raw_material_id', $rawMaterialId)
                ->update($data);

            $updated = OfferRawMaterialCalculated::where('offer_id', $offerId)
                ->where('raw_material_id', $rawMaterialId)
                ->first();

            return new OfferRawMaterialCalculatedResource($updated);
        }

        // raw_material_id تم تغييره
        $newRawMaterialId = $data['raw_material_id'];
        unset($data['raw_material_id']);

        DB::transaction(function () use ($offerId, $rawMaterialId, $data, $newRawMaterialId) {
            OfferRawMaterial::where('offer_id', $offerId)
                ->where('raw_material_id', $rawMaterialId)
                ->delete();

            OfferRawMaterial::create([
                'offer_id' => $offerId,
                'raw_material_id' => $newRawMaterialId,
                ...$data,
            ]);
        });

        $updated = OfferRawMaterialCalculated::where('offer_id', $offerId)
            ->where('raw_material_id', $newRawMaterialId)
            ->first();

        return new OfferRawMaterialCalculatedResource($updated);
    }



    public function updateRawMaterialDemand(array $data, int $offerId, int $rawMaterialId): OfferRawMaterialCalculatedResource
    {
        // 1. Update the absolut_demand
        OfferRawMaterial::where('offer_id', $offerId)
            ->where('raw_material_id', $rawMaterialId)
            ->update($data);

        // 2. Get all raw materials for recalculating
        $allMaterials = OfferRawMaterial::where('offer_id', $offerId)->get();
        $totalDemand = $allMaterials->sum('absolut_demand');

        if ($totalDemand > 0) {
            // Step 2: Calculate initial unrounded shares
            $shares = [];
            foreach ($allMaterials as $material) {
                $unrounded = ($material->absolut_demand / $totalDemand) * 100;
                $shares[] = [
                    'material' => $material,
                    'unrounded' => $unrounded,
                    'rounded' => round($unrounded, 2),
                ];
            }

            // Step 3: Adjust rounding to make sum = 100
            $roundedSum = array_sum(array_column($shares, 'rounded'));
            $diff = round(100 - $roundedSum, 2);

            // Find the material with the highest unrounded value to absorb the diff
            usort($shares, fn($a, $b) => $b['unrounded'] <=> $a['unrounded']);
            $shares[0]['rounded'] += $diff;

            // Step 4: Update DB
            foreach ($shares as $s) {
                OfferRawMaterial::where('offer_id', $offerId)
                    ->where('raw_material_id', $s['material']->raw_material_id)
                    ->update([
                        'share' => $s['rounded'],
                    ]);
            }
        }
        // 3. Try to get the updated material safely
        $updated = OfferRawMaterialCalculated::where('offer_id', $offerId)
            ->where('raw_material_id', $rawMaterialId)
            ->first();

        if (!$updated) {
            abort(404, 'Raw material not found for this offer.');
        }

        return new OfferRawMaterialCalculatedResource($updated);
    }

    public function recalculateShares(int $offerId): void
    {
        $allMaterials = OfferRawMaterial::where('offer_id', $offerId)->get();
        $totalDemand = $allMaterials->sum('absolut_demand');

        if ($totalDemand > 0) {
            $shares = [];
            foreach ($allMaterials as $material) {
                $unrounded = ($material->absolut_demand / $totalDemand) * 100;
                $shares[] = [
                    'material' => $material,
                    'unrounded' => $unrounded,
                    'rounded' => round($unrounded, 2),
                ];
            }

            $roundedSum = array_sum(array_column($shares, 'rounded'));
            $diff = round(100 - $roundedSum, 2);

            usort($shares, fn($a, $b) => $b['unrounded'] <=> $a['unrounded']);
            if (count($shares) > 0) {
                $shares[0]['rounded'] += $diff;
            }

            foreach ($shares as $s) {
                OfferRawMaterial::where('offer_id', $offerId)
                    ->where('raw_material_id', $s['material']->raw_material_id)
                    ->update([
                        'share' => $s['rounded'],
                    ]);
            }
        }
    }

    /**
     * Sync prices from raw_materials to offers_raw_materials for a specific offer
     */
    public function syncPricesFromRawMaterials(int $offerId): void
    {
        $offerRawMaterials = OfferRawMaterial::where('offer_id', $offerId)->get();
        
        foreach ($offerRawMaterials as $offerRawMaterial) {
            $rawMaterial = \App\Models\RawMaterial::find($offerRawMaterial->raw_material_id);
            
            if ($rawMaterial && ($rawMaterial->price !== null || $rawMaterial->price_date !== null)) {
                $updateData = [];
                
                if ($rawMaterial->price !== null) {
                    $updateData['price'] = $rawMaterial->price;
                }
                
                if ($rawMaterial->price_date !== null) {
                    $updateData['price_date'] = $rawMaterial->price_date;
                }
                
                if (!empty($updateData)) {
                    OfferRawMaterial::where('offer_id', $offerId)
                        ->where('raw_material_id', $offerRawMaterial->raw_material_id)
                        ->update($updateData);
                }
            }
        }
    }
}
