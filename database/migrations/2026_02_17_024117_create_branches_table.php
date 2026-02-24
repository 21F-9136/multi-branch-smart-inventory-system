<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('address', 255)->nullable();

            // Manager column WITHOUT foreign key (staged approach)
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->timestamps();

            $table->index('manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
