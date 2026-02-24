<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Inventory belongs to a branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Inventory belongs to a product
    public function product()
{
    return $this->belongsTo(\App\Models\Product::class)->withTrashed();
}

}
