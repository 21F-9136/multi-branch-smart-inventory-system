<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BranchService;
use App\Models\Branch;

class BranchController extends Controller
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    // List Branches (with pagination + optional search)
    public function index(Request $request)
    {
        $branches = $this->branchService->list(
            $request->search,
            $request->per_page ?? 10
        );

        return response()->json($branches);
    }

    // Create Branch
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'location' => 'nullable|string|max:255',
        ]);

        $branch = $this->branchService->create(
            $request->only('name', 'location')
        );

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch
        ], 201);
    }

    // Update Branch
    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'location' => 'nullable|string|max:255',
        ]);

        $updated = $this->branchService->update(
            $branch,
            $request->only('name', 'location')
        );

        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $updated
        ]);
    }

    // Delete Branch
    public function destroy(Branch $branch)
    {
        $this->branchService->delete($branch);

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

    // Assign Manager
    public function assignManager(Request $request, Branch $branch)
    {
        $request->validate([
            'manager_id' => 'required|exists:users,id'
        ]);

        try {
            $updated = $this->branchService->assignManager(
                $branch,
                $request->manager_id
            );

            return response()->json([
                'message' => 'Manager assigned successfully',
                'data' => $updated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
