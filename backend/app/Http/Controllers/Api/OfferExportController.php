<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Offer;
use App\Models\OfferCalculated;
use App\Models\OfferCalculatedWordExport;
use App\Models\OfferRawMaterialCalculated;
use App\Services\WordExportService;
use App\Models\OffersRawMaterialsCalculated;
use Illuminate\Support\Facades\Log;

class OfferExportController extends BaseController
{
    protected $wordExportService;

    public function __construct(WordExportService $wordExportService)
    {
        $this->wordExportService = $wordExportService;
    }

    public function getWordExportData($id)
    {
        $offer = OfferCalculatedWordExport::findOrFail($id);
        return $offer;
    }

    public function export($id)
    {
        try {
            $templateFilename = request()->query('template');

            $offer = OfferCalculatedWordExport::findOrFail($id);
            $offerData = $offer->toArray();

            // Base placeholders from offer data
            $placeholders = [];
            foreach ($offerData as $key => $value) {
                $placeholders[$key] = $value ?? '-';
            }

            // Fetch raw materials
            $rawMaterials = OfferRawMaterialCalculated::where('offer_id', $id)->get();
            $maxMaterials = 10; // You can adjust this based on your DOCX template

            // Get all possible keys from first material, fallback to a predefined list
            $defaultKeys = collect($rawMaterials)
                ->map(fn($item) => array_keys($item->toArray()))
                ->flatten()
                ->unique()
                ->values()
                ->all();

            // Loop through all expected material slots (e.g., 1 to 10)
            for ($i = 1; $i <= $maxMaterials; $i++) {
                $material = $rawMaterials[$i - 1] ?? null;
                $data = $material ? $material->toArray() : [];

                foreach ($defaultKeys as $key) {
                    $placeholders["{$key}{$i}"] = $data[$key] ?? '-';
                }
            }

            $filename = 'Offer_' . ($offer->general_offer_number ?? 'DEFAULT');

            Log::info('Exporting offer', ['offer_id' => $id, 'template' => $templateFilename]);

            return $this->wordExportService->exportOfferWithTemplate($placeholders, $filename, $templateFilename);
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'offer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
