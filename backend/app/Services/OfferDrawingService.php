<?php


namespace App\Services;

use App\Models\Offer;
use App\Models\OfferDrawing;
use App\Repositories\OfferDrawingRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferDrawingService
{
    public function __construct(private OfferDrawingRepository $repository) {}


    public function storeDrawing(Offer $offer, UploadedFile $file): OfferDrawing
    {
        $year     = now()->year;
        $basePath = config('offer_drawings.base_path');

        $name      = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename  = Str::slug($name) . '.' . $extension;

        $relativePath = "{$basePath}/{$year}";
        Storage::disk('public')->putFileAs($relativePath, $file, $filename);

        return $this->repository->create([
            'offer_id'    => $offer->id,
            'filename'    => $filename,
            'upload_date' => now(),
        ]);
    }




    public function getLatestDrawing(Offer $offer): ?OfferDrawing
    {
        return $this->repository->getLatestByOffer($offer->id);
    }
}
