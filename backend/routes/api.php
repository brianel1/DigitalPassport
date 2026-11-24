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
use App\Http\Controllers\AssistantController;

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'digitalpassport-api',
    ]);
});

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/shared-profile/{token}', [\App\Http\Controllers\ProfileShareController::class, 'show'])->middleware('throttle:60,1');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(AuthController::userData($request->user()));
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/assistant/sessions', [AssistantController::class, 'index']);
    Route::post('/assistant/sessions', [AssistantController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/assistant/sessions/{session}', [AssistantController::class, 'show']);
    Route::delete('/assistant/sessions/{session}', [AssistantController::class, 'destroy']);
    Route::post('/assistant/sessions/{session}/messages', [AssistantController::class, 'message'])->middleware('throttle:10,1');
    Route::get('/student/profile', [StudentProfileController::class, 'show']);
    Route::put('/student/profile', [StudentProfileController::class, 'update']);
    Route::post('/student/progress/visit', [\App\Http\Controllers\StudentProgressController::class, 'visit']);
    Route::get('/student/profile-shares', [\App\Http\Controllers\ProfileShareController::class, 'index']);
    Route::post('/student/profile-shares/preview', [\App\Http\Controllers\ProfileShareController::class, 'preview']);
    Route::post('/student/profile-shares', [\App\Http\Controllers\ProfileShareController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('/student/profile-shares/{share}', [\App\Http\Controllers\ProfileShareController::class, 'destroy']);
    Route::get('/student/passport/export', [PassportController::class, 'export']);
    Route::apiResource('/student/skills', SkillController::class)->except(['show']);
    Route::apiResource('/student/projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('/student/certifications', CertificationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/evidence/{file}', [\App\Http\Controllers\EvidenceController::class, 'show']);
    Route::get('/student/assessments', [StudentAssessmentController::class, 'index']);
    Route::post('/student/assessments/{assessment}/attempts', [StudentAssessmentController::class, 'start']);
    Route::post('/student/assessment-attempts/{attempt}/submit', [StudentAssessmentController::class, 'submit']);
    Route::get('/student/job-preferences', [JobRecommendationController::class, 'preferences']);
    Route::put('/student/job-preferences', [JobRecommendationController::class, 'updatePreferences']);
    Route::get('/student/job-recommendations', [JobRecommendationController::class, 'latest']);
    Route::post('/student/job-recommendations', [JobRecommendationController::class, 'generate']);
    Route::put('/student/jobs/{listing}/feedback', [JobRecommendationController::class, 'feedback']);
    Route::get('/student/cvs', [GeneratedCvController::class, 'index']);
    Route::post('/student/cvs', [GeneratedCvController::class, 'store']);
    Route::post('/student/cvs/upload', [GeneratedCvController::class, 'upload'])->middleware('throttle:12,1');
    Route::post('/student/cvs/{cv}/structure', [GeneratedCvController::class, 'structure'])->middleware('throttle:6,1');
    Route::post('/student/cvs/{cv}/review', [GeneratedCvController::class, 'review'])->middleware('throttle:6,1');
    Route::post('/student/cvs/{cv}/duplicate', [GeneratedCvController::class, 'duplicate']);
    Route::get('/student/cvs/{cv}/source', [GeneratedCvController::class, 'source']);
    Route::delete('/student/cvs/{cv}', [GeneratedCvController::class, 'destroy']);
    Route::put('/student/cvs/{cv}', [GeneratedCvController::class, 'update']);
    Route::patch('/student/cvs/{cv}/name', [GeneratedCvController::class, 'rename']);
    Route::get('/student/cvs/{cv}/export/{format}', [GeneratedCvController::class, 'export']);
    Route::middleware('admin')->group(function () {
        Route::apiResource('/admin/assessments', AdminAssessmentController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::put('/admin/assessments/{assessment}', [AdminAssessmentController::class, 'update']);
        Route::post('/admin/assessments/{assessment}/toggle-publish', [AdminAssessmentController::class, 'togglePublish']);
        Route::post('/admin/assessments/{assessment}/versions', [AdminAssessmentController::class, 'newVersion']);
        Route::post('/admin/assessments/{assessment}/questions', [AdminAssessmentController::class, 'storeQuestion']);
        Route::put('/admin/assessments/{assessment}/questions/{question}', [AdminAssessmentController::class, 'updateQuestion']);
        Route::delete('/admin/assessments/{assessment}/questions/{question}', [AdminAssessmentController::class, 'destroyQuestion']);
        Route::get('/admin/reviews', [AdminReviewController::class, 'index']);
        Route::get('/admin/reviews/{type}/{id}/evidence/{file}', [AdminReviewController::class, 'evidence']);
        Route::put('/admin/reviews/{type}/{id}', [AdminReviewController::class, 'update']);
        Route::get('/admin/dashboard-stats', [AdminDashboardController::class, 'stats']);
        Route::get('/admin/system-status', [\App\Http\Controllers\SystemStatusController::class, 'show']);
        Route::post('/admin/system-status/sync', [\App\Http\Controllers\SystemStatusController::class, 'sync'])->middleware('throttle:2,1');
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/admin/users/{user}/status', [AdminUserController::class, 'updateStatus']);
    });
});
