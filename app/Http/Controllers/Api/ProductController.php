<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['category', 'supplier'])
            ->search($request->string('search')->toString())
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create($request->validated())
            ->load(['category', 'supplier']);

        return ProductResource::make($product);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['category', 'supplier']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return ProductResource::make($product->load(['category', 'supplier']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(status: 204);
    }

    public function stockIn(AdjustStockRequest $request, Product $product): ProductResource
    {
        $product->increment('quantity', $request->integer('quantity'));

        return ProductResource::make($product->refresh()->load(['category', 'supplier']));
    }

    public function stockOut(AdjustStockRequest $request, Product $product): ProductResource
    {
        $product->decrement('quantity', $request->integer('quantity'));

        return ProductResource::make($product->refresh()->load(['category', 'supplier']));
    }
}
