<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['projects', 'certifications'] as $table) Schema::table($table, function (Blueprint $t) {
            $t->unsignedInteger('submission_revision')->default(1);
            $t->timestamp('submitted_at')->nullable();
        });
        Schema::table('verification_reviews', fn (Blueprint $t) => $t->json('record_snapshot')->nullable());
        Schema::table('assessments', function (Blueprint $t) {
            $t->json('skill_names')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('parent_assessment_id')->nullable()->constrained('assessments')->nullOnDelete();
        });
        Schema::table('assessment_attempts', fn (Blueprint $t) => $t->json('assessment_snapshot')->nullable());
        Schema::table('job_recommendation_runs', fn (Blueprint $t) => $t->json('ai_insights')->nullable());
        Schema::table('generated_cvs', fn (Blueprint $t) => $t->json('provenance')->nullable());
        Schema::table('job_listings', function (Blueprint $t) {
            $t->timestamp('link_checked_at')->nullable();
            $t->unsignedSmallInteger('link_status')->nullable();
        });
        Schema::create('job_feedback', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $t->string('rating', 20); $t->string('reason', 500)->nullable(); $t->timestamps();
            $t->unique(['user_id', 'job_listing_id']);
        });
        Schema::create('job_sync_runs', function (Blueprint $t) {
            $t->id(); $t->string('source', 40); $t->string('status', 20); $t->unsignedInteger('saved_count')->default(0);
            $t->string('message', 500)->nullable(); $t->timestamp('started_at'); $t->timestamp('completed_at')->nullable(); $t->timestamps();
        });
        Schema::create('system_events', function (Blueprint $t) {
            $t->id(); $t->string('kind', 40); $t->string('status', 20); $t->string('message', 500);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $t->timestamps();
        });
        Schema::create('profile_shares', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('token_hash', 64)->unique(); $t->text('encrypted_token'); $t->json('selection');
            $t->timestamp('expires_at'); $t->timestamp('revoked_at')->nullable(); $t->timestamps();
        });
        Schema::create('student_visits', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->json('snapshot'); $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['student_visits', 'profile_shares', 'system_events', 'job_sync_runs', 'job_feedback'] as $table) Schema::dropIfExists($table);
        Schema::table('job_listings', fn (Blueprint $t) => $t->dropColumn(['link_checked_at', 'link_status']));
        Schema::table('generated_cvs', fn (Blueprint $t) => $t->dropColumn('provenance'));
        Schema::table('job_recommendation_runs', fn (Blueprint $t) => $t->dropColumn('ai_insights'));
        Schema::table('assessment_attempts', fn (Blueprint $t) => $t->dropColumn('assessment_snapshot'));
        Schema::table('assessments', function (Blueprint $t) { $t->dropConstrainedForeignId('parent_assessment_id'); $t->dropColumn(['skill_names', 'version']); });
        Schema::table('verification_reviews', fn (Blueprint $t) => $t->dropColumn('record_snapshot'));
        foreach (['projects', 'certifications'] as $table) Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['submission_revision', 'submitted_at']));
    }
};
