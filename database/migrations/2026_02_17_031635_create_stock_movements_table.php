<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('branch_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Movement Type (Strict ERP Control)
            $table->enum('type', ['IN', 'OUT', 'RESERVE', 'RELEASE']);

            // Quantity affected
            $table->integer('quantity');

            // Reference (Order ID etc.)
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();

            // Performance Indexes
            $table->index(['branch_id', 'product_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};