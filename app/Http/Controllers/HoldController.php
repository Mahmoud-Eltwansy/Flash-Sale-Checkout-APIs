<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHoldRequest;
use App\Services\HoldService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class HoldController extends Controller
{
    use ApiResponse;
    protected $holdService;

    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    public function store(CreateHoldRequest $request)
    {
        $validated = $request->validated();

        try {
            $hold = $this->holdService->createHold($validated['product_id'], $validated['quantity']);

            return $this->successResponse($hold, 'Hold Created successfully', 201);
        } catch (\Throwable  $e) {
            return $this->errorResponse($e->getMessage(), 'Error', $e->getCode());
        }
    }
}
