<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'cost_price',
        'sale_price',
        'tax_percentage',
        'status'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Product has many inventory records (per branch)
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // Product appears in many order items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Optional helper: active scope
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
