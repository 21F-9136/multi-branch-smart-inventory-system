<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get Branch Report Dashboard (🔐 Secured)
     */
    public function getBranchReport(int $branchId)
    {
        $user = auth()->user();

        // 🔐 Branch Isolation
        if ($user->role_id != 1) {
            if ($branchId != $user->branch_id) {
                throw ValidationException::withMessages([
                    'report' => ['You are not authorized to view this branch report.']
                ]);
            }
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // =========================
        // Total Sales Today
        // =========================
        $todaySales = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('grand_total');

        // =========================
        // Total Sales This Month
        // =========================
        $monthlySales = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('grand_total');

        // =========================
        // Total Completed Orders
        // =========================
        $totalOrders = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->count();

        // =========================
        // Top 5 Selling Products
        // =========================
        $topProducts = OrderItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->whereHas('order', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->with('product:id,name,sku')
            ->get();

        // =========================
        // Low Stock Items (threshold: <=5)
        // =========================
        $lowStock = Inventory::where('branch_id', $branchId)
            ->whereRaw('(quantity - reserved_quantity) <= 5')
            ->with('product:id,name,sku')
            ->get();

        return [
            'today_sales'    => $todaySales,
            'monthly_sales'  => $monthlySales,
            'total_orders'   => $totalOrders,
            'top_products'   => $topProducts,
            'low_stock'      => $lowStock,
        ];
    }
}