<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->integer('quantity');

            $table->timestamps();

            // Composite unique index (CRITICAL)
            $table->unique(['branch_id', 'product_id']);

            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
