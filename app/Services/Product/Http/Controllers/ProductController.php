<?php

namespace App\Services\Product\Http\Controllers;



use App\Http\Controllers\Controller;
use App\Services\Product\Http\Requests\StoreProductRequest;
use App\Services\Product\Http\Requests\UpdateProductRequest;
use App\Services\Product\Http\Resources\ProductResource;
use App\Services\Product\Repositories\ProductRepositoryInterface;
use App\Services\Product\Events\ProductStockUpdated;
use App\Traits\ApiResponse;
use App\Constants\ErrorCode;
use Illuminate\Http\JsonResponse;

/**
 * Product Controller
 *
 * Handles product CRUD operations with API resources
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * ProductController constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    )
    {
    }

    /**
     * Display a listing of products.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = $this->productRepository->all();

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productRepository->create($request->validated());

        return $this->createdResponse(
            new ProductResource($product),
            'Product created successfully'
        );
    }

    /**
     * Display the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::PRODUCT_NOT_FOUND),
                ErrorCode::PRODUCT_NOT_FOUND
            );
        }

        return $this->successResponse(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product.
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $oldProduct = $this->productRepository->find($id);

        if (!$oldProduct) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::PRODUCT_NOT_FOUND),
                ErrorCode::PRODUCT_NOT_FOUND
            );
        }

        $product = $this->productRepository->update($id, $request->validated());

        // Dispatch event if stock quantity changed
        if ($request->has('stock_quantity') && $oldProduct->stock_quantity !== $product?->stock_quantity) {
            event(new ProductStockUpdated($product, $oldProduct->stock_quantity));
        }

        return $this->successResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::PRODUCT_NOT_FOUND),
                ErrorCode::PRODUCT_NOT_FOUND
            );
        }

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }
}
