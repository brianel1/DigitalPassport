<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certification;
use App\Models\GeneratedCv;
use App\Models\JobRecommendationRun;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\VerificationReview;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $pendingProjects = Project::where('status', 'pending')->count();
        $pendingCerts = Certification::where('status', 'pending')->count();
        $pendingSkills = Skill::where('status', 'pending')->count();
        $pendingDates = Project::where('status','pending')->get()->map(fn($r)=>$r->submitted_at??$r->created_at)
            ->concat(Certification::where('status','pending')->get()->map(fn($r)=>$r->submitted_at??$r->created_at))
            ->concat(Skill::where('status','pending')->pluck('created_at'));

        $reviewsThisWeek = VerificationReview::where('created_at', '>=', now()->startOfWeek())->count();

        $students = User::where('role', 'student');
        $monthStart = now()->startOfMonth()->subMonths(5);
        $userGrowth = collect(CarbonPeriod::create($monthStart, '1 month', now()->startOfMonth()))
            ->map(function ($month) {
                $start = $month->copy()->startOfMonth();
                return [
                    'label' => $start->format('M Y'),
                    'value' => User::where('role', 'student')
                        ->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])
                        ->count(),
                ];
            })->values();

        $submissionCounts = collect(['pending', 'verified', 'rejected'])
            ->mapWithKeys(fn (string $status) => [
                $status => Skill::where('status', $status)->count()
                    + Project::where('status', $status)->count()
                    + Certification::where('status', $status)->count(),
            ]);

        $attempts = AssessmentAttempt::whereNotNull('submitted_at');

        return response()->json([
            'pendingReviews' => $pendingProjects + $pendingCerts + $pendingSkills,
            'waitingOverWeek' => $pendingDates->filter(fn($date)=>$date->lte(now()->subDays(7)))->count(),
            'oldestPendingDays' => $pendingDates->isEmpty()?0:(int)abs($pendingDates->sort()->first()->diffInDays(now())),
            'totalStudents' => $students->count(),
            'activeStudents' => (clone $students)->where('is_active', true)->count(),
            'inactiveStudents' => (clone $students)->where('is_active', false)->count(),
            'totalAssessments' => Assessment::count(),
            'reviewsThisWeek' => $reviewsThisWeek,
            'totalCvGenerated' => GeneratedCv::count(),
            'totalRecommendationRuns' => JobRecommendationRun::where('status', 'completed')->count(),
            'assessmentAttempts' => (clone $attempts)->count(),
            'assessmentPassRate' => round((float) ((clone $attempts)->count() > 0
                ? ((clone $attempts)->where('passed', true)->count() / (clone $attempts)->count()) * 100
                : 0), 1),
            'userGrowth' => $userGrowth,
            'submissionCounts' => $submissionCounts,
            'assessmentOutcomes' => [
                'passed' => (clone $attempts)->where('passed', true)->count(),
                'notPassed' => (clone $attempts)->where('passed', false)->count(),
            ],
        ]);
    }
}
