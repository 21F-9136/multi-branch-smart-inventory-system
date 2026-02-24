<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'manager_id'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Branch has many users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Branch manager (one user)
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Branch has many inventories
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // Branch has many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
