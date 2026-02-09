<?php

namespace App\Services\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Product\Events\ProductStockUpdated;
use App\Services\Product\Http\Requests\StoreProductRequest;
use App\Services\Product\Http\Requests\UpdateProductRequest;
use App\Services\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * ProductController constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of products.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = $this->productRepository->all();

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ], 200);
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

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product created successfully'
        ], 201);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Product not found',
                    'code' => 'PRODUCT_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product retrieved successfully'
        ], 200);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Product not found',
                    'code' => 'PRODUCT_NOT_FOUND'
                ]
            ], 404);
        }

        $product = $this->productRepository->update($id, $request->validated());

        // Dispatch event if stock quantity changed
        if ($request->has('stock_quantity') && $oldProduct->stock_quantity !== $product->stock_quantity) {
            event(new ProductStockUpdated($product, $oldProduct->stock_quantity));
        }

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product updated successfully'
        ], 200);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Product not found',
                    'code' => 'PRODUCT_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
