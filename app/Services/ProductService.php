<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product)
    {
        $product->delete(); // Soft delete
    }

    // 🔵 Updated list method (Search + Sorting + Pagination)
    public function list(
        $search = null,
        $perPage = 10,
        $sortBy = 'id',
        $sortDir = 'desc'
    ) {
        $query = Product::query();

        // 🔍 Search (Name + SKU)
        if ($search) {
            $search = trim($search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // 🔐 Sorting protection (Whitelist allowed columns)
        $allowedSorts = [
            'id',
            'name',
            'sku',
            'cost_price',
            'sale_price',
            'tax_percentage',
            'status'
        ];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }
}