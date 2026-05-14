<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $productRepository) {}

    public function index(): JsonResponse
    {
        $products = $this->productRepository->getActive();

        return response()->json(ProductResource::collection($products));
    }

    public function byCategory(int $categoryId): JsonResponse
    {
        $products = $this->productRepository->getByCategoryId($categoryId);

        return response()->json(ProductResource::collection($products));
    }

    public function search(): JsonResponse
    {
        $query = request()->input('q');

        if (empty($query)) {
            return response()->json([]);
        }

        $products = $this->productRepository->search($query);

        return response()->json(ProductResource::collection($products));
    }

    public function show(Product $product): JsonResource
    {
        return response()->json(new ProductResource($product->load('category', 'modifiers')));
    }
}
