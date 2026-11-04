<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\AdminAssessmentController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\JobRecommendationController;
use App\Http\Controllers\GeneratedCvController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PassportController;

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'digitalpassport-api',
    ]);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(AuthController::userData($request->user()));
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/student/profile', [StudentProfileController::class, 'show']);
    Route::put('/student/profile', [StudentProfileController::class, 'update']);
    Route::get('/student/passport/export', [PassportController::class, 'export']);
    Route::apiResource('/student/skills', SkillController::class)->except(['show']);
    Route::apiResource('/student/projects', ProjectController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('/student/certifications', CertificationController::class)->only(['index', 'store', 'destroy']);
    Route::get('/student/assessments', [StudentAssessmentController::class, 'index']);
    Route::post('/student/assessments/{assessment}/attempts', [StudentAssessmentController::class, 'start']);
    Route::post('/student/assessment-attempts/{attempt}/submit', [StudentAssessmentController::class, 'submit']);
    Route::apiResource('/admin/assessments', AdminAssessmentController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::put('/admin/assessments/{assessment}', [AdminAssessmentController::class, 'update']);
    Route::post('/admin/assessments/{assessment}/toggle-publish', [AdminAssessmentController::class, 'togglePublish']);
    Route::post('/admin/assessments/{assessment}/questions', [AdminAssessmentController::class, 'storeQuestion']);
    Route::put('/admin/assessments/{assessment}/questions/{question}', [AdminAssessmentController::class, 'updateQuestion']);
    Route::delete('/admin/assessments/{assessment}/questions/{question}', [AdminAssessmentController::class, 'destroyQuestion']);
    Route::get('/admin/reviews', [AdminReviewController::class, 'index']);
    Route::get('/admin/reviews/{type}/{id}/evidence/{file}', [AdminReviewController::class, 'evidence']);
    Route::put('/admin/reviews/{type}/{id}', [AdminReviewController::class, 'update']);
    Route::get('/admin/dashboard-stats', [AdminDashboardController::class, 'stats']);
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/admin/users/{user}/status', [AdminUserController::class, 'updateStatus']);
    Route::get('/student/job-preferences', [JobRecommendationController::class, 'preferences']);
    Route::put('/student/job-preferences', [JobRecommendationController::class, 'updatePreferences']);
    Route::get('/student/job-recommendations', [JobRecommendationController::class, 'latest']);
    Route::post('/student/job-recommendations', [JobRecommendationController::class, 'generate']);
    Route::get('/student/cvs', [GeneratedCvController::class, 'index']);
    Route::post('/student/cvs', [GeneratedCvController::class, 'store']);
    Route::put('/student/cvs/{cv}', [GeneratedCvController::class, 'update']);
    Route::get('/student/cvs/{cv}/export/{format}', [GeneratedCvController::class, 'export']);
});
