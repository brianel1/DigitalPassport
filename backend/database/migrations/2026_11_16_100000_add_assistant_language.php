<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('assistant_sessions', fn(Blueprint $table) => $table->string('language',2)->nullable()); }
    public function down(): void { Schema::table('assistant_sessions', fn(Blueprint $table) => $table->dropColumn('language')); }
};
