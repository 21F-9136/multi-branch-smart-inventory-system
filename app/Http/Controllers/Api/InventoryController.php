<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * List Inventory (with available stock)
     */
    public function index(Request $request)
    {
        $inventories = $this->inventoryService->list(
            $request->branch_id
        );

        return response()->json([
            'data' => $inventories
        ]);
    }

    /**
     * Add Stock
     */
    public function addStock(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = $this->inventoryService->addStock(
            $request->branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Stock added successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Remove Stock
     */
    public function removeStock(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = $this->inventoryService->removeStock(
            $request->branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Stock removed successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Reserve Stock
     */
    public function reserve(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = $this->inventoryService->reserveStock(
            $request->branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Stock reserved successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Release Reserved Stock
     */
    public function release(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = $this->inventoryService->releaseStock(
            $request->branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Stock released successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Transfer Stock Between Branches
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id'   => 'required|exists:branches,id|different:from_branch_id',
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
        ]);

        $result = $this->inventoryService->transferStock($validated);

        return response()->json([
            'message' => 'Stock transferred successfully.',
            'data'    => $result
        ]);
    }
}