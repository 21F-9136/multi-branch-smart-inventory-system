<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1️⃣ Orders Composite Index
        Schema::table('orders', function (Blueprint $table) {
            $table->index(
                ['branch_id', 'status', 'created_at'],
                'orders_branch_status_created_index'
            );
        });

        // 2️⃣ Stock Movements created_at Index
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(
                'created_at',
                'stock_movements_created_at_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_branch_status_created_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_index');
        });
    }
};