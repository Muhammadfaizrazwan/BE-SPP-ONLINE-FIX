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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('school_id');
            $table->string('code', 20); // BCA_VA, GOPAY, DANA, etc.
            $table->string('name', 100); // BCA Virtual Account, GoPay, etc.
            $table->enum('type', ['bank_transfer', 'e_wallet', 'cash', 'qris', 'va']);
            $table->string('provider', 50)->nullable(); // Midtrans, Xendit, etc.
            $table->string('account_number', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
