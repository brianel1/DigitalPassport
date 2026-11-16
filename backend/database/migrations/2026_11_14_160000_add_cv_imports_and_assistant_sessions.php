<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->string('source_type', 30)->default('profile');
            $table->string('source_filename')->nullable();
            $table->string('source_path')->nullable();
            $table->longText('source_text')->nullable();
            $table->json('analysis')->nullable();
        });
        Schema::create('assistant_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_cv_id')->nullable()->constrained('generated_cvs')->nullOnDelete();
            $table->string('title', 120)->default('New conversation');
            $table->string('mode', 20)->default('coach');
            $table->json('messages');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_sessions');
        Schema::table('generated_cvs', fn (Blueprint $table) => $table->dropColumn(['source_type', 'source_filename', 'source_path', 'source_text', 'analysis']));
    }
};
