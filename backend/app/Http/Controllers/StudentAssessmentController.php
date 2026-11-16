<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentEvidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $assessments = Assessment::where('is_published', true)->withCount('questions')->orderBy('title')->get()->map(fn ($a) => $this->assessmentData($a));
        $attempts = $request->user()->assessmentAttempts()->whereNotNull('submitted_at')->with('assessment')->latest('submitted_at')->get()->map(fn ($a) => $this->attemptData($a));
        return response()->json(['assessments' => $assessments, 'attempts' => $attempts]);
    }

    public function start(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureStudent($request);
        $attempt = DB::transaction(function () use ($request, $assessment) {
            $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->first();
            $assessment = Assessment::whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            $pending = AssessmentAttempt::where('assessment_id', $assessment->id)->where('user_id', $request->user()->id)->whereNull('submitted_at')->latest()->get();
            foreach ($pending as $existing) {
                $snapshot = $existing->assessment_snapshot ?? app(AssessmentEvidence::class)->snapshot($assessment);
                if (!$existing->assessment_snapshot) $existing->update(['assessment_snapshot' => $snapshot]);
                if (now()->lt($existing->started_at->copy()->addMinutes($snapshot['time_limit']))) return $existing;
                $this->finish($existing, [], true);
            }
            abort_unless($assessment->is_published, 404);
            abort_unless($assessment->questions()->exists(), 422, 'This assessment has no questions yet.');
            return AssessmentAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $request->user()->id, 'started_at' => now(), 'assessment_snapshot' => app(AssessmentEvidence::class)->snapshot($assessment)]);
        });
        $snapshot = $attempt->assessment_snapshot;
        return response()->json([
            'attemptId' => $attempt->id, 'startedAt' => $attempt->started_at->toISOString(),
            'expiresAt' => $attempt->started_at->copy()->addMinutes($snapshot['time_limit'])->toISOString(),
            'assessment' => [
                'id' => $assessment->id, 'title' => $snapshot['title'], 'category' => $snapshot['category'], 'description' => $snapshot['description'],
                'timeLimit' => $snapshot['time_limit'], 'passingScore' => $snapshot['passing_score'], 'questionCount' => count($snapshot['questions']),
                'questions' => collect($snapshot['questions'])->map(fn ($q) => collect($q)->only(['id', 'question', 'options'])->all())->values(),
            ],
        ], 201);
    }

    public function submit(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->ensureStudent($request);
        abort_unless($attempt->user_id === $request->user()->id, 404);
        $validated = $request->validate(['answers' => ['present', 'array'], 'answers.*.question_id' => ['required', 'integer', 'distinct'], 'answers.*.selected_option' => ['nullable', 'integer', 'min:0']]);
        $attempt = DB::transaction(function () use ($attempt, $validated) {
            $attempt = AssessmentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            abort_if($attempt->submitted_at, 422, 'This attempt has already been submitted.');
            if (!$attempt->assessment_snapshot) $attempt->update(['assessment_snapshot' => app(AssessmentEvidence::class)->snapshot($attempt->assessment)]);
            $expired = now()->gt($attempt->started_at->copy()->addMinutes($attempt->assessment_snapshot['time_limit'])->addSeconds(10));
            return $this->finish($attempt, $validated['answers'], $expired);
        });
        return response()->json($this->attemptData($attempt->load('assessment')));
    }

    private function finish(AssessmentAttempt $attempt, array $answers, bool $expired): AssessmentAttempt
    {
        $snapshot = $attempt->assessment_snapshot;
        $submitted = collect($expired ? [] : $answers)->keyBy('question_id');
        $maximum = collect($snapshot['questions'])->sum('points'); $score = 0;
        foreach ($snapshot['questions'] as $question) {
            $selected = $submitted->get($question['id'])['selected_option'] ?? null;
            $correct = $selected !== null && (int) $selected === $question['correct_option'];
            $points = $correct ? $question['points'] : 0; $score += $points;
            $attempt->answers()->create(['question_id' => $question['id'], 'selected_option' => $selected, 'is_correct' => $correct, 'points_awarded' => $points]);
        }
        $percentage = $maximum > 0 ? round($score / $maximum * 100, 2) : 0;
        $snapshot['expired'] = $expired;
        $attempt->update(['assessment_snapshot' => $snapshot, 'score' => $score, 'maximum_score' => $maximum, 'percentage' => $percentage, 'passed' => !$expired && $percentage >= $snapshot['passing_score'], 'submitted_at' => now()]);
        return $attempt;
    }

    private function ensureStudent(Request $request): void { abort_unless($request->user()->role === 'student', 403); }

    private function assessmentData(Assessment $a): array
    {
        return ['id' => $a->id, 'title' => $a->title, 'category' => $a->category, 'description' => $a->description,
            'questionCount' => $a->questions_count, 'timeLimit' => $a->time_limit_minutes, 'passingScore' => $a->passing_score, 'skillNames' => $a->skill_names ?? [], 'version' => $a->version];
    }

    private function attemptData(AssessmentAttempt $a): array
    {
        $snapshot = $a->assessment_snapshot;
        return ['id' => $a->id, 'assessmentId' => $a->assessment_id, 'familyId' => $snapshot['family_id'] ?? $a->assessment->parent_assessment_id ?? $a->assessment_id,
            'assessmentTitle' => $snapshot['title'] ?? $a->assessment->title, 'category' => $snapshot['category'] ?? $a->assessment->category,
            'score' => $a->score, 'totalQuestions' => isset($snapshot['questions']) ? count($snapshot['questions']) : $a->assessment->questions()->count(),
            'percentage' => (float) $a->percentage, 'passed' => $a->passed, 'completedAt' => $a->submitted_at?->toISOString(),
            'skillNames' => app(AssessmentEvidence::class)->skills($a), 'expired' => $snapshot['expired'] ?? false,
            'feedback' => ($snapshot['expired'] ?? false) ? 'The time limit ended. Start a new attempt when you are ready.'
                : ($a->passed ? 'You passed. These results support the assessment topics in your skills record.' : 'Review '.implode(', ', app(AssessmentEvidence::class)->skills($a) ?: [str_replace('-', ' ', $snapshot['category'] ?? $a->assessment->category)]).' before trying again.'),
        ];
    }
}
