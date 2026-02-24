<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BranchService
{
    /*
    |--------------------------------------------------------------------------
    | Create Branch
    |--------------------------------------------------------------------------
    */
    public function create(array $data)
    {
        return Branch::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Branch
    |--------------------------------------------------------------------------
    */
    public function update(Branch $branch, array $data)
    {
        $branch->update($data);
        return $branch->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Branch
    |--------------------------------------------------------------------------
    */
    public function delete(Branch $branch)
    {
        $branch->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Branch Manager
    |--------------------------------------------------------------------------
    */
    public function assignManager(Branch $branch, int $managerId)
    {
        $manager = User::find($managerId);

        if (!$manager) {
            throw ValidationException::withMessages([
                'user_id' => ['Selected user does not exist.']
            ]);
        }

        // Ensure user is Branch Manager (role_id = 2)
        if ($manager->role_id != 2) {
            throw ValidationException::withMessages([
                'user_id' => ['Selected user must have Branch Manager role.']
            ]);
        }

        $branch->manager_id = $manager->id;
        $branch->save();

        return $branch->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | List Branches (Search + Pagination)
    |--------------------------------------------------------------------------
    */
    public function list(?string $search = null, int $perPage = 10)
    {
        $query = Branch::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->latest()->paginate($perPage);
    }
}
