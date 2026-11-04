<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('preferred_fields')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->json('work_types')->nullable();
            $table->boolean('accepts_remote')->default(true);
            $table->unsignedInteger('minimum_salary')->nullable();
            $table->unsignedInteger('maximum_salary')->nullable();
            $table->timestamps();
        });

        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40)->index();
            $table->string('external_id', 191);
            $table->string('title');
            $table->string('company');
            $table->string('location')->nullable();
            $table->string('work_type', 50)->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('salary_currency', 10)->nullable();
            $table->text('description');
            $table->json('required_skills')->nullable();
            $table->string('application_url');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fetched_at')->useCurrent();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->index(['title', 'location']);
        });

        Schema::create('job_recommendation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('profile_snapshot');
            $table->json('preference_snapshot')->nullable();
            $table->string('model_name')->nullable();
            $table->string('status', 20)->default('completed');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_run_id')->constrained('job_recommendation_runs')->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->decimal('match_score', 5, 2);
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->text('explanation');
            $table->unsignedSmallInteger('rank');
            $table->timestamps();
            $table->unique(['recommendation_run_id', 'job_listing_id']);
            $table->index(['recommendation_run_id', 'rank']);
        });

        Schema::create('generated_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_job_listing_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->string('title');
            $table->json('content');
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->string('model_name')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('cv_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_cv_id')->constrained()->cascadeOnDelete();
            $table->string('format', 10);
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_exports');
        Schema::dropIfExists('generated_cvs');
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('job_recommendation_runs');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('job_preferences');
    }
};
