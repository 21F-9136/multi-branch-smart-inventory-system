<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Super Admin')->first();

        User::create([
            'role_id' => $adminRole->id,
            'branch_id' => null,
            'name' => 'Super Admin',
            'email' => 'admin@erp.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
