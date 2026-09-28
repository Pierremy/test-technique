<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * Liste paginée, avec recherche, filtres et tri.
     */
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $products = Product::query()
            ->with('category')
            // Recherche par nom (et par SKU, pratique pour retrouver un produit précis).
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']))
            ->when(isset($filters['in_stock']), fn ($query) => $request->boolean('in_stock')
                ? $query->where('stock', '>', 0)
                : $query->where('stock', 0))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            // Tri secondaire pour une pagination stable quand les valeurs triées sont égales.
            ->orderBy('id', 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create($request->validated());

        // Une ressource construite sur un modèle tout juste créé répond en 201.
        return new ProductResource($product->load('category'));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
