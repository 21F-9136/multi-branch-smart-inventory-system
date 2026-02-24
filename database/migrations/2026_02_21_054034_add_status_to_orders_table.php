<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add status column to orders table
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // Order lifecycle status
            // draft → confirmed → cancelled → completed
            $table->string('status', 30)
                  ->default('draft')
                  ->after('user_id')
                  ->comment('Order lifecycle status: draft, confirmed, cancelled, completed');

            // Index for fast filtering by status (important for dashboards & reports)
            $table->index('status');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropIndex(['status']);
            $table->dropColumn('status');

        });
    }
};