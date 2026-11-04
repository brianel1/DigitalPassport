<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAssessmentController extends Controller
{
    private const CATEGORIES = ['programming', 'databases', 'web-development', 'cybersecurity', 'data-analysis', 'networking', 'cloud-computing', 'other'];

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $items = Assessment::withCount('questions')->latest()->get()->map(fn (Assessment $assessment) => $this->data($assessment));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['required', 'string', 'max:4000'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $assessment = Assessment::create([
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'time_limit_minutes' => $validated['time_limit'],
            'passing_score' => $validated['passing_score'],
            'is_published' => false,
        ]);

        return response()->json($this->data($assessment), 201);
    }

    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureAdmin($request);
        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted successfully.']);
    }

    public function update(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['required', 'string', 'max:4000'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $assessment->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'time_limit_minutes' => $validated['time_limit'],
            'passing_score' => $validated['passing_score'],
        ]);

        return response()->json($this->data($assessment->refresh()->loadCount('questions')));
    }

    /**
     * Toggle published status.
     */
    public function togglePublish(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureAdmin($request);

        if (!$assessment->is_published && $assessment->questions()->count() === 0) {
            return response()->json(['message' => 'Cannot publish an assessment with no questions.'], 422);
        }

        $assessment->update(['is_published' => !$assessment->is_published]);

        return response()->json([
            'message' => $assessment->is_published ? 'Assessment published.' : 'Assessment unpublished.',
            'isPublished' => $assessment->is_published,
        ]);
    }

    /**
     * Show assessment with questions (for admin editing).
     */
    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureAdmin($request);
        $assessment->loadCount('questions');

        return response()->json([
            ...$this->data($assessment),
            'questions' => $assessment->questions()->orderBy('position')->get()->map(fn (AssessmentQuestion $q) => [
                'id' => $q->id,
                'question' => $q->question,
                'options' => $q->options,
                'correctOption' => $q->correct_option,
                'points' => $q->points,
                'position' => $q->position,
            ]),
        ]);
    }

    /**
     * Add a question to an assessment.
     */
    public function storeQuestion(Request $request, Assessment $assessment): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string', 'max:500'],
            'correct_option' => ['required', 'integer', 'min:0'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        abort_if(
            $validated['correct_option'] >= count($validated['options']),
            422,
            'The correct option index is out of range.'
        );

        $maxPosition = $assessment->questions()->max('position') ?? 0;

        $question = $assessment->questions()->create([
            'question' => $validated['question'],
            'options' => $validated['options'],
            'correct_option' => $validated['correct_option'],
            'points' => $validated['points'] ?? 1,
            'position' => $maxPosition + 1,
        ]);

        return response()->json([
            'id' => $question->id,
            'question' => $question->question,
            'options' => $question->options,
            'correctOption' => $question->correct_option,
            'points' => $question->points,
            'position' => $question->position,
        ], 201);
    }

    /**
     * Update a question.
     */
    public function updateQuestion(Request $request, Assessment $assessment, AssessmentQuestion $question): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($question->assessment_id === $assessment->id, 404);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string', 'max:500'],
            'correct_option' => ['required', 'integer', 'min:0'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        abort_if(
            $validated['correct_option'] >= count($validated['options']),
            422,
            'The correct option index is out of range.'
        );

        $question->update([
            'question' => $validated['question'],
            'options' => $validated['options'],
            'correct_option' => $validated['correct_option'],
            'points' => $validated['points'] ?? $question->points,
        ]);

        return response()->json([
            'id' => $question->id,
            'question' => $question->question,
            'options' => $question->options,
            'correctOption' => $question->correct_option,
            'points' => $question->points,
            'position' => $question->position,
        ]);
    }

    /**
     * Delete a question.
     */
    public function destroyQuestion(Request $request, Assessment $assessment, AssessmentQuestion $question): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($question->assessment_id === $assessment->id, 404);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Administrator access is required.');
    }

    /** @return array<string, mixed> */
    private function data(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'category' => $assessment->category,
            'description' => $assessment->description,
            'questionCount' => $assessment->questions_count ?? 0,
            'timeLimit' => $assessment->time_limit_minutes,
            'passingScore' => $assessment->passing_score,
            'isPublished' => $assessment->is_published,
        ];
    }
}
