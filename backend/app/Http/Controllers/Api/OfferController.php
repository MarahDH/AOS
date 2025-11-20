<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResponse;
use App\Http\Collections\OfferCollection;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Offer\UpdateOfferFieldRequest;
use App\Models\Offer;
use App\Models\OfferStatus;
use App\Services\OfferService;
use App\Services\AdditiveOfferRawMaterialService;
use App\Services\RawMaterialService;
use App\Services\OfferRawMaterialService;
use App\Services\OfferStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class OfferController extends BaseController
{
    public function __construct(
        private OfferService $service,
        private OfferStatusService $offerStatusService,
        private AdditiveOfferRawMaterialService $additiveOfferRawMaterialService,
        private RawMaterialService $rawMaterialService,
        private OfferRawMaterialService $offerRawMaterialService

    ) {}

    public function index()
    {
        return ApiResponse::success(new OfferCollection($this->service->getAllSummary()));
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->getOfferById($id));
    }

    /**
     * First blur: create offer with single field
     */
    public function store(Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $userId = Auth::user()->id;


        try {
            $offer = $this->service->createOfferFromField(
                $request->input('field'),
                $request->input('value'),
                $userId
            );

            $updated_offer = $this->service->getOfferById($offer->id);

            return ApiResponse::success([
                'id' => $offer->id,
                'offer' => $updated_offer->fresh(['createdByUser']),
            ], 'Offer created');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Subsequent blur: update field
     */
    public function update(UpdateOfferFieldRequest $request, $id)
    {
        try {
            $offer = Offer::findOrFail($id);

            $this->service->updateField(
                $offer,
                $request->input('field'),
                $request->input('value')
            );

            $updated_offer = $this->service->getOfferById($offer->id);

            return ApiResponse::success([
                'id' => $offer->id,
                'offer' => $updated_offer
            ], 'Field updated');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }


    public function duplicate($id)
    {
        try {
            $newOffer = $this->service->duplicateOffer($id);

            return ApiResponse::success([
                'id' => $newOffer->id,
                'offer' => $newOffer->fresh(['createdByUser']),
            ], 'Offer duplicated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to duplicate offer: ' . $e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $offer = Offer::findOrFail($id);

            $deletedStatus = OfferStatus::where('name', 'Gelöscht')->first();

            if (!$deletedStatus) {
                return ApiResponse::error('Status "Gelöscht" nicht gefunden', 404);
            }

            $offer->general_status_id = $deletedStatus->id;
            $offer->save();

            return ApiResponse::success(null, 'Angebot als gelöscht markiert');
        } catch (\Exception $e) {
            return ApiResponse::error('Fehler beim Löschen des Angebots: ' . $e->getMessage(), 400);
        }
    }



    public function getEditableFields(int $id)
    {
        $user = Auth::user();
        $offer = Offer::findOrFail($id);

        $fields = $this->service->editableFieldsByRoleAndStatus($user, $offer);

        return ApiResponse::success($fields, 'Editable fields retrieved successfully');
    }

    public function getOfferData(int $id)
    {
        $user = Auth::user();
        $offer = Offer::findOrFail($id);

        // Get all the data needed for the offer page in one request (excluding offer data since it's already fetched separately)
        $data = [
            'editable_fields' => $this->service->editableFieldsByRoleAndStatus($user, $offer),
            'offer_statuses' => $this->offerStatusService->getAll(),
            'additives' => $this->additiveOfferRawMaterialService->getAllAdditives(),
            'raw_materials' => $this->rawMaterialService->getAllMaterials(),
            'raw_materials_calculated' => $this->offerRawMaterialService->getRawMaterialsCalculated($id),
        ];

        return ApiResponse::success($data, 'Offer data retrieved successfully');
    }

    public function getTemplates()
    {
        $templatePath = storage_path('app/templates');
        if (!File::exists($templatePath)) {
            return ApiResponse::success([], 'No templates folder found');
        }

        $files = File::files($templatePath);
        $docxFiles = collect($files)
            ->filter(fn($file) => $file->getExtension() === 'docx')
            ->map(fn($file) => $file->getFilename())
            ->values();

        return ApiResponse::success($docxFiles, 'Templates fetched');
    }
}
