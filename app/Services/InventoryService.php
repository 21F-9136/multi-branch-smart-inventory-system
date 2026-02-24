<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Add stock (Stock In)
     */
    public function addStock($branchId, $productId, $quantity)
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {

            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = Inventory::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);

                $inventory = Inventory::where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
            }

            $inventory->quantity += $quantity;
            $inventory->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'IN',
                'quantity' => $quantity,
            ]);

            return $inventory;
        });
    }

    /**
     * Remove stock (Stock Out)
     */
    public function removeStock($branchId, $productId, $quantity)
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {

            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough available stock.']
                ]);
            }

            $inventory->quantity -= $quantity;
            $inventory->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'OUT',
                'quantity' => $quantity,
            ]);

            return $inventory;
        });
    }

    /**
     * Reserve stock
     */
    public function reserveStock($branchId, $productId, $quantity)
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {

            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough available stock.']
                ]);
            }

            $inventory->reserved_quantity += $quantity;
            $inventory->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'RESERVE',
                'quantity' => $quantity,
            ]);

            return $inventory;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseStock($branchId, $productId, $quantity)
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {

            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventory->reserved_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Cannot release more than reserved quantity.']
                ]);
            }

            $inventory->reserved_quantity -= $quantity;
            $inventory->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'RELEASE',
                'quantity' => $quantity,
            ]);

            return $inventory;
        });
    }

    /**
     * Transfer stock between branches (🔐 Secured)
     */
    public function transferStock(array $data)
    {
        $user = auth()->user();

        // 🔐 Branch Isolation
        if ($user->role_id != 1) {

            if ($data['from_branch_id'] != $user->branch_id) {
                throw ValidationException::withMessages([
                    'transfer' => ['You are not authorized to transfer from this branch.']
                ]);
            }

            if ($data['to_branch_id'] != $user->branch_id) {
                throw ValidationException::withMessages([
                    'transfer' => ['You cannot transfer stock to another branch.']
                ]);
            }
        }

        return DB::transaction(function () use ($data) {

            $fromBranch = $data['from_branch_id'];
            $toBranch   = $data['to_branch_id'];
            $productId  = $data['product_id'];
            $quantity   = $data['quantity'];

            $sourceInventory = Inventory::where('branch_id', $fromBranch)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$sourceInventory) {
                throw ValidationException::withMessages([
                    'inventory' => ['Source inventory not found.']
                ]);
            }

            $available = $sourceInventory->quantity - $sourceInventory->reserved_quantity;

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough available stock to transfer.']
                ]);
            }

            $sourceInventory->quantity -= $quantity;
            $sourceInventory->save();

            $destinationInventory = Inventory::where('branch_id', $toBranch)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$destinationInventory) {
                $destinationInventory = Inventory::create([
                    'branch_id' => $toBranch,
                    'product_id' => $productId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $destinationInventory->quantity += $quantity;
            $destinationInventory->save();

            StockMovement::create([
                'branch_id' => $fromBranch,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'OUT_TRANSFER',
                'quantity' => $quantity,
            ]);

            StockMovement::create([
                'branch_id' => $toBranch,
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'type' => 'IN_TRANSFER',
                'quantity' => $quantity,
            ]);

            return [
                'from_branch_id' => $fromBranch,
                'to_branch_id'   => $toBranch,
                'product_id'     => $productId,
                'quantity'       => $quantity,
            ];
        });
    }

    /**
     * List inventory with calculated available stock
     */
    public function list($branchId = null)
    {
        $user = auth()->user();

        $query = Inventory::with(['branch', 'product']);

        if ($user->role_id == 1) {

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $inventories = $query->get();

        return $inventories->map(function ($item) {
            return [
                'id' => $item->id,
                'branch' => $item->branch->name ?? null,
                'product' => $item->product->name ?? null,
                'quantity' => $item->quantity,
                'reserved_quantity' => $item->reserved_quantity,
                'available_stock' => $item->quantity - $item->reserved_quantity,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });
    }
}