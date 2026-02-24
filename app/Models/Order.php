<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'branch_id',
        'user_id',
        'subtotal',
        'tax_total',
        'grand_total',
        'status'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Order belongs to a branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Order belongs to a user (salesperson)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order has many items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}