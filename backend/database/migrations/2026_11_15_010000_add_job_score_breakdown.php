<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('job_matches', fn (Blueprint $table) => $table->json('score_breakdown')->nullable()); }
    public function down(): void { Schema::table('job_matches', fn (Blueprint $table) => $table->dropColumn('score_breakdown')); }
};
