<?php

namespace Tests\Feature;

use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_authored_assessment_bank_has_ten_scored_questions_per_topic(): void
    {
        $this->seed();

        $assessments = Assessment::with('questions')->get();
        $this->assertCount(2, $assessments);
        foreach ($assessments as $assessment) {
            $this->assertTrue($assessment->is_published);
            $this->assertCount(10, $assessment->questions);
            $this->assertSame(range(1, 10), $assessment->questions->pluck('position')->sort()->values()->all());
        }
    }
}
