<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $bank = json_decode(file_get_contents(database_path('assessment_bank.json')), true);
        foreach ($bank as $entry) {
            foreach (\App\Models\Assessment::with('questions')->where('title', $entry['title'])->whereNull('skill_names')->get() as $assessment) {
                $current = $assessment->questions->sortBy('position')->map(fn ($q) => $q->only(['question', 'options', 'correct_option']))->values()->all();
                // Only map the exact authored question bank, never a similarly named custom test.
                if ($current !== $entry['questions']) continue;
                $assessment->update(['skill_names' => $entry['skill_names']]);
            }
        }
        // Freeze legacy attempts using the currently stored authored questions; historical edits cannot be reconstructed.
        \App\Models\AssessmentAttempt::with('assessment.questions')->whereNull('assessment_snapshot')->chunkById(100, function ($attempts) {
            foreach ($attempts as $attempt) if ($attempt->assessment) $attempt->update(['assessment_snapshot' => app(\App\Services\AssessmentEvidence::class)->snapshot($attempt->assessment)]);
        });
    }
    public function down(): void { /* Evidence snapshots are deliberately retained. */ }
};
