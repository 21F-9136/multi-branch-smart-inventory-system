<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Inventory;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    // ===============================
    // List Products (Search + Pagination + Sorting)
    // ===============================
    public function index(Request $request)
    {
        $products = $this->productService->list(
            $request->search,
            $request->per_page ?? 10,
            $request->sort_by ?? 'id',
            $request->sort_dir ?? 'desc'
        );

        return response()->json($products);
    }

    // ===============================
    // Create Product + Auto Create Inventory
    // ===============================
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:100|unique:products,sku',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0|gte:cost_price',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        return DB::transaction(function () use ($request) {

            // 1️⃣ Create Product
            $product = $this->productService->create(
                $request->only([
                    'name',
                    'sku',
                    'cost_price',
                    'sale_price',
                    'tax_percentage',
                    'status'
                ])
            );

            // 2️⃣ Auto Create Inventory For All Branches
            $branches = Branch::all();

            foreach ($branches as $branch) {
                Inventory::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        });
    }

    // ===============================
    // Update Product
    // ===============================
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0|gte:cost_price',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $updated = $this->productService->update(
            $product,
            $request->only([
                'name',
                'sku',
                'cost_price',
                'sale_price',
                'tax_percentage',
                'status'
            ])
        );

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $updated
        ]);
    }

    // ===============================
    // Delete Product (Soft Delete)
    // ===============================
    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}