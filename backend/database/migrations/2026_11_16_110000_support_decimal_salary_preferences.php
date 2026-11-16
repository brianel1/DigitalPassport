<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_preferences', function (Blueprint $table) {
            $table->decimal('minimum_salary',12,2)->nullable()->change();
            $table->decimal('maximum_salary',12,2)->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('job_preferences', function (Blueprint $table) {
            $table->unsignedInteger('minimum_salary')->nullable()->change();
            $table->unsignedInteger('maximum_salary')->nullable()->change();
        });
    }
};
