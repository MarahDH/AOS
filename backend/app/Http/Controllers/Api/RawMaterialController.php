<?php

namespace App\Http\Controllers\Api;

use App\Http\Collections\RawMaterialCollection;
use App\Http\Controllers\BaseController;
use App\Http\Requests\RawMaterial\CreateRawMaterialRequest;
use App\Http\Requests\RawMaterial\UpdateRawMaterialRequest;
use App\Http\Resources\ApiResponse;
use App\Services\RawMaterialService;

class RawMaterialController extends BaseController
{
    //
    public function __construct(private RawMaterialService $service) {}

    public function index()
    {
        return ApiResponse::success(new RawMaterialCollection($this->service->getAllMaterials()));
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->getRawMaterialById($id));
    }

    public function store(CreateRawMaterialRequest $request)
    {

        $data = $request->all();

        $rawMaterial = $this->service->createRawMaterial($data);

        return ApiResponse::success([
            'id' => $rawMaterial->id,
            'raw_material' => $rawMaterial->fresh(['createdByUser']),
        ], 'Raw Material created');
    }

    public function update(UpdateRawMaterialRequest $request, $id)
    {
        $material = $this->service->updateRawMaterial($id, $request->validated());

        return ApiResponse::success($material->fresh());
    }
}
