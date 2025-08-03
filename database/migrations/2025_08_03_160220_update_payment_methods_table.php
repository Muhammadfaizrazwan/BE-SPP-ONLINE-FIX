<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'school_id')) {
                $table->uuid('school_id')->after('id');
            }
            if (!Schema::hasColumn('payment_methods', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('payment_methods', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['school_id', 'description', 'is_active']);
        });
    }
};
