<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create Order (Draft)
     */
    public function createOrder(array $data)
    {
        $user = auth()->user();

        // 🔐 Branch Isolation
        if ($user->role_id != 1 && $data['branch_id'] != $user->branch_id) {
            throw ValidationException::withMessages([
                'branch' => ['You are not authorized to create order for this branch.']
            ]);
        }

        return DB::transaction(function () use ($data) {

            $subtotal = 0;
            $taxTotal = 0;

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'branch_id'    => $data['branch_id'],
                'user_id'      => auth()->id(),
                'subtotal'     => 0,
                'tax_total'    => 0,
                'grand_total'  => 0,
                'status'       => 'draft'
            ]);

            foreach ($data['items'] as $itemData) {

                $product = Product::where('id', $itemData['product_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'product' => ['Invalid or inactive product.']
                    ]);
                }

                $unitPrice = $product->sale_price;
                $quantity  = $itemData['quantity'];

                $lineSubtotal = $unitPrice * $quantity;
                $lineTax      = ($lineSubtotal * $product->tax_percentage) / 100;
                $lineTotal    = $lineSubtotal + $lineTax;

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->update([
                'subtotal'    => $subtotal,
                'tax_total'   => $taxTotal,
                'grand_total' => $subtotal + $taxTotal
            ]);

            return $order->load('items.product');
        });
    }

    /**
     * Change Order Status
     */
    public function changeStatus(Order $order, string $newStatus)
    {
        $user = auth()->user();

        // 🔐 Branch Isolation
        if ($user->role_id != 1 && $order->branch_id != $user->branch_id) {
            throw ValidationException::withMessages([
                'order' => ['You are not authorized to access this order.']
            ]);
        }

        $allowedTransitions = [
            'draft'     => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $currentStatus = $order->status;

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from {$currentStatus} to {$newStatus}."]
            ]);
        }

        return DB::transaction(function () use ($order, $newStatus) {

            if ($newStatus === 'confirmed') {
                $this->handleConfirm($order);
            }

            if ($newStatus === 'cancelled') {
                $this->handleCancel($order);
            }

            if ($newStatus === 'completed') {
                $this->handleComplete($order);
            }

            $order->status = $newStatus;
            $order->save();

            return $order->fresh(['items.product']);
        });
    }

    /**
     * CONFIRM → Reserve Stock
     */
    protected function handleConfirm(Order $order)
    {
        foreach ($order->items as $item) {

            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw ValidationException::withMessages([
                    'inventory' => ['Inventory record not found.']
                ]);
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ["Insufficient stock for product ID {$item->product_id}."]
                ]);
            }

            // Reserve stock
            $inventory->reserved_quantity += $item->quantity;
            $inventory->save();

            StockMovement::create([
                'product_id' => $item->product_id,
                'branch_id'  => $order->branch_id,
                'type'       => 'RESERVE',
                'quantity'   => $item->quantity,
                'user_id'    => auth()->id(),
            ]);
        }
    }

    /**
     * CANCEL → Release Reserved Stock
     */
    protected function handleCancel(Order $order)
    {
        if ($order->status !== 'confirmed') {
            return;
        }

        foreach ($order->items as $item) {

            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw ValidationException::withMessages([
                    'inventory' => ['Inventory record not found.']
                ]);
            }

            $inventory->reserved_quantity -= $item->quantity;
            $inventory->save();

            StockMovement::create([
                'product_id' => $item->product_id,
                'branch_id'  => $order->branch_id,
                'type'       => 'RELEASE',
                'quantity'   => $item->quantity,
                'user_id'    => auth()->id(),
            ]);
        }
    }

    /**
     * COMPLETE → Deduct Physical Stock
     */
    protected function handleComplete(Order $order)
    {
        foreach ($order->items as $item) {

            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw ValidationException::withMessages([
                    'inventory' => ['Inventory record not found.']
                ]);
            }

            if ($inventory->reserved_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ['Reserved quantity mismatch detected.']
                ]);
            }

            // Deduct actual stock
            $inventory->quantity -= $item->quantity;
            $inventory->reserved_quantity -= $item->quantity;
            $inventory->save();

            StockMovement::create([
                'product_id' => $item->product_id,
                'branch_id'  => $order->branch_id,
                'type'       => 'OUT',
                'quantity'   => $item->quantity,
                'user_id'    => auth()->id(),
            ]);
        }
    }
}