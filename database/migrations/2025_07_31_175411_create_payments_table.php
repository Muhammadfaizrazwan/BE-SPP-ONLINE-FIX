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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 50)->unique();
            $table->uuid('student_id');
            $table->json('bill_ids'); // Array of bill IDs yang dibayar
            $table->bigInteger('payment_method_id')->unsigned();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->timestamp('payment_date');
            $table->string('payment_proof', 255)->nullable(); // URL bukti transfer
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled'])->default('pending');
            $table->string('gateway_reference', 100)->nullable(); // Reference dari payment gateway
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('restrict');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['student_id', 'status']);
            $table->index(['payment_date', 'status']);
            $table->index(['gateway_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
