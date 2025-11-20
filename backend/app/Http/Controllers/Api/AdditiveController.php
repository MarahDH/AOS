<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ApiResponse;
use App\Services\AdditiveOfferRawMaterialService;

class AdditiveController extends BaseController
{

    public function __construct(private AdditiveOfferRawMaterialService $service) {}
    
    public function index()
    {
        $additives = $this->service->getAllAdditives();

        return ApiResponse::success($additives);
    }
}
