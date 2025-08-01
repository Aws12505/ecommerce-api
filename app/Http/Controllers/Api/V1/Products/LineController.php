<?php
// app/Http/Controllers/Api/V1/Products/LineController.php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lines\StoreLineRequest;
use App\Http\Requests\V1\Lines\UpdateLineRequest;
use App\Http\Requests\V1\Lines\AttachProductToLineRequest;
use App\Services\V1\Products\LineService;
use App\Models\Line;
use App\Models\Product;

class LineController extends Controller
{
    protected LineService $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    public function index()
    {
        try {
            $activeOnly = request()->boolean('active_only', true);
            $lines = $this->lineService->getAllLines($activeOnly);

            return response()->json([
                'success' => true,
                'message' => 'Lines retrieved successfully',
                'data' => $lines,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lines',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $line = $this->lineService->getLineBySlug($slug);

            return response()->json([
                'success' => true,
                'message' => 'Line retrieved successfully',
                'data' => $line,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Line not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(StoreLineRequest $request)
    {
        try {
            $line = $this->lineService->createLine($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Line created successfully',
                'data' => $line,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create line',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateLineRequest $request, Line $line)
    {
        try {
            $updatedLine = $this->lineService->updateLine($line, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Line updated successfully',
                'data' => $updatedLine,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update line',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Line $line)
    {
        try {
            $this->lineService->deleteLine($line);

            return response()->json([
                'success' => true,
                'message' => 'Line deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete line',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function attachProduct(AttachProductToLineRequest $request, Line $line)
    {
        try {
            $this->lineService->attachProductToLine(
                $line,
                $request->validated()['product_id'],
                $request->validated()['sort_order'] ?? 0
            );

            return response()->json([
                'success' => true,
                'message' => 'Product attached to line successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach product to line',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function detachProduct(Line $line, Product $product)
    {
        try {
            $this->lineService->detachProductFromLine($line, $product);

            return response()->json([
                'success' => true,
                'message' => 'Product detached from line successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach product from line',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
