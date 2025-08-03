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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->string('student_number', 20);
            $table->string('nis', 20)->nullable();
            $table->string('nisn', 20)->nullable();
            $table->string('full_name', 100);
            $table->enum('gender', ['L', 'P']); // L = Laki-laki, P = Perempuan
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('parent_name', 100)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->string('parent_email', 100)->nullable();
            $table->uuid('user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['school_id', 'student_number']);
            $table->unique(['school_id', 'nis']);
            $table->unique(['school_id', 'nisn']);
            $table->index(['school_id', 'is_active']);
            $table->index(['full_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
