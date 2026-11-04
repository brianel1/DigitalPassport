<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->after('password')->index();
            $table->string('programme', 100)->nullable()->after('role');
            $table->string('matric_no', 40)->nullable()->unique()->after('programme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matric_no']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'programme', 'matric_no']);
        });
    }
};
