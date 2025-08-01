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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();
            $table->uuid('school_id')->nullable();
            $table->string('action', 50); // created, updated, deleted, login, logout, payment, etc.
            $table->string('table_name', 50);
            $table->string('record_id', 50); // Support UUID dan integer
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');

            $table->index(['user_id', 'created_at']);
            $table->index(['school_id', 'created_at']);
            $table->index(['action', 'table_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
