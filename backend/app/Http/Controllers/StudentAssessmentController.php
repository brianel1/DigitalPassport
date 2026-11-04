<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);

        $assessments = Assessment::query()
            ->where('is_published', true)
            ->withCount('questions')
            ->orderBy('title')
            ->get()
            ->map(fn (Assessment $assessment) => $this->assessmentData($assessment));

        $attempts = AssessmentAttempt::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->with('assessment')
            ->latest('submitted_at')
            ->get()
            ->map(fn (AssessmentAttempt $attempt) => $this->attemptData($attempt));

        return response()->json(['assessments' => $assessments, 'attempts' => $attempts]);
    }

    public function start(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureStudent($request);
        abort_unless($assessment->is_published, 404);

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        $assessment->load('questions');

        return response()->json([
            'attemptId' => $attempt->id,
            'startedAt' => $attempt->started_at->toISOString(),
            'expiresAt' => $attempt->started_at->copy()->addMinutes($assessment->time_limit_minutes)->toISOString(),
            'assessment' => [
                ...$this->assessmentData($assessment),
                'questions' => $assessment->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'options' => $question->options,
                ]),
            ],
        ], 201);
    }

    public function submit(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->ensureStudent($request);
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->submitted_at) {
            return response()->json(['message' => 'This assessment attempt has already been submitted.'], 422);
        }

        $assessment = $attempt->assessment()->with('questions')->firstOrFail();
        if (now()->greaterThan($attempt->started_at->copy()->addMinutes($assessment->time_limit_minutes)->addSeconds(10))) {
            return response()->json(['message' => 'The assessment time limit has expired.'], 422);
        }

        $validated = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_option' => ['nullable', 'integer', 'min:0'],
        ]);

        $submittedAnswers = collect($validated['answers'])->keyBy('question_id');
        $score = 0;
        $maximumScore = $assessment->questions->sum('points');

        foreach ($assessment->questions as $question) {
            $selected = $submittedAnswers->get($question->id)['selected_option'] ?? null;
            $isCorrect = $selected !== null && (int) $selected === $question->correct_option;
            $points = $isCorrect ? $question->points : 0;
            $score += $points;
            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option' => $selected,
                'is_correct' => $isCorrect,
                'points_awarded' => $points,
            ]);
        }

        $percentage = $maximumScore > 0 ? round(($score / $maximumScore) * 100, 2) : 0;
        $attempt->update([
            'score' => $score,
            'maximum_score' => $maximumScore,
            'percentage' => $percentage,
            'passed' => $percentage >= $assessment->passing_score,
            'submitted_at' => now(),
        ]);

        return response()->json($this->attemptData($attempt->refresh()->load('assessment')));
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    /** @return array<string, mixed> */
    private function assessmentData(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'category' => $assessment->category,
            'description' => $assessment->description,
            'questionCount' => $assessment->questions_count ?? $assessment->questions->count(),
            'timeLimit' => $assessment->time_limit_minutes,
            'passingScore' => $assessment->passing_score,
        ];
    }

    /** @return array<string, mixed> */
    private function attemptData(AssessmentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'assessmentId' => $attempt->assessment_id,
            'assessmentTitle' => $attempt->assessment->title,
            'category' => $attempt->assessment->category,
            'score' => $attempt->score,
            'totalQuestions' => $attempt->assessment->questions()->count(),
            'percentage' => (float) $attempt->percentage,
            'passed' => $attempt->passed,
            'completedAt' => $attempt->submitted_at?->toISOString(),
        ];
    }
}
