<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;

class DashboardController extends Controller
{
    public function branchSummary(Request $request)
    {
        $user = $request->user();

        $branchId = $user->branch_id;

        if (!$branchId) {
            return response()->json([
                'message' => 'User is not assigned to any branch'
            ], 400);
        }

        // Total products in this branch
        $totalProducts = Inventory::where('branch_id', $branchId)->count();

        // Total available stock (quantity - reserved_quantity)
        $totalAvailableStock = Inventory::where('branch_id', $branchId)
            ->sum(\DB::raw('quantity - reserved_quantity'));

        // Low stock count (quantity <= 2)
        $lowStockCount = Inventory::where('branch_id', $branchId)
            ->where('quantity', '<=', 2)
            ->count();

        return response()->json([
            'total_products' => $totalProducts,
            'total_available_stock' => $totalAvailableStock,
            'low_stock_count' => $lowStockCount,
        ]);
    }
    # sale summary for the logged in user
    public function salesSummary(Request $request)
{
    $user = $request->user();

    $totalOrders = \App\Models\Order::where('user_id', $user->id)->count();

    $completedOrders = \App\Models\Order::where('user_id', $user->id)
        ->where('status', 'completed')
        ->count();

    $pendingOrders = \App\Models\Order::where('user_id', $user->id)
        ->where('status', 'pending')
        ->count();

    return response()->json([
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'pending_orders' => $pendingOrders,
    ]);
}
}