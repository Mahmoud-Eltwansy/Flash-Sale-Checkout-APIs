<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    use ApiResponse;
    public function show($id)
    {
        $product = $this->getProductById($id);
        if (!$product)
            return $this->errorResponse($product, 'Product Not Found', 404);
        return $this->successResponse($product);
    }
    public function getProductById($id)
    {
        $cacheKey = "product:{$id}:data";
        $cacheTtl = 30;
        return Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
            $product = Product::find($id);
            if (!$product)
                return null;
            return [
                'id' => $product->id,
                'name' => $product->name,
                'available_stock' => $product->available_stock,
                'price' => $product->price,
            ];
        });
    }
}
