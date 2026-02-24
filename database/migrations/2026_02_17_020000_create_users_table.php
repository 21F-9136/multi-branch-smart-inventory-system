<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Role FK (safe)
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            // Branch column WITHOUT foreign key (staged approach)
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255);

            $table->rememberToken();
            $table->timestamps();

            $table->index('role_id');
            $table->index('branch_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
