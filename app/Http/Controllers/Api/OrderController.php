<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * 📦 List Orders (Enterprise Level Listing)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Order::with(['items.product'])
            ->orderByDesc('created_at');

        // 🔐 Branch Isolation
        if ($user->role_id != 1) {
            $query->where('branch_id', $user->branch_id);
        }

        // 🔎 Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🔎 Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // 🔎 Search by order number
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        // 📄 Pagination (default 10)
        $perPage = $request->get('per_page', 10);

        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Create Order (Draft)
     */
    public function store(Request $request)
{
    $user = auth()->user();

    $validated = $request->validate([
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1'
    ]);

    // ✅ Automatically assign branch from logged-in user
    $validated['branch_id'] = $user->branch_id;

    $order = $this->orderService->createOrder($validated);

    return response()->json([
        'message' => 'Order created successfully.',
        'data' => $order
    ], 201);
}

    /**
     * Change Order Status
     */
    public function changeStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['draft', 'confirmed', 'cancelled', 'completed'])
            ]
        ]);

        $updatedOrder = $this->orderService->changeStatus(
            $order,
            $validated['status']
        );

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data'    => $updatedOrder
        ]);
    }
}