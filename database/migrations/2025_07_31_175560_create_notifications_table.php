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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('school_id');
            $table->uuid('user_id')->nullable(); // specific user, null = broadcast
            $table->uuid('student_id')->nullable(); // related student
            $table->string('title', 255);
            $table->text('message');
            $table->enum('type', ['payment_reminder', 'payment_success', 'overdue', 'system', 'announcement']);
            $table->json('channels'); // ['email', 'sms', 'whatsapp', 'in_app']
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            $table->index(['user_id', 'read_at']);
            $table->index(['school_id', 'type', 'status']);
            $table->index(['student_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
