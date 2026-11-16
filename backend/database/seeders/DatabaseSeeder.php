<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // An administrator is provisioned only from explicit local credentials.
        if (filled(env('ADMIN_EMAIL')) && filled(env('ADMIN_PASSWORD'))) {
            User::updateOrCreate(['email' => env('ADMIN_EMAIL')], [
                'name' => env('ADMIN_NAME', 'Skills & Career Admin'),
                'password' => env('ADMIN_PASSWORD'),
                'role' => 'admin',
                'programme' => null,
                'matric_no' => null,
            ]);
        }

        $bank = json_decode(file_get_contents(database_path('assessment_bank.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($bank as $assessmentData) {
            $questions = $assessmentData['questions'];
            unset($assessmentData['questions']);
            // Existing authored or administrator-edited assessments are never overwritten by a seed.
            if (Assessment::where('title', $assessmentData['title'])->exists()) continue;
            $assessment = Assessment::firstOrCreate(
                ['title' => $assessmentData['title']],
                [...$assessmentData, 'is_published' => true],
            );
            foreach ($questions as $position => $question) {
                $assessment->questions()->updateOrCreate(
                    ['position' => $position + 1],
                    [...$question, 'points' => 1],
                );
            }
        }
    }
}
