<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assessment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $student = User::updateOrCreate(['email' => 'elber@student.utem.edu.my'], [
            'name' => 'Elber Anselmus',
            'password' => 'password123',
            'role' => 'student',
            'programme' => 'BITS',
            'matric_no' => 'B032510581',
        ]);

        foreach ([
            ['name' => 'PHP', 'category' => 'programming', 'proficiency' => 'advanced'],
            ['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate'],
            ['name' => 'MySQL', 'category' => 'databases', 'proficiency' => 'intermediate'],
            ['name' => 'JavaScript', 'category' => 'programming', 'proficiency' => 'intermediate'],
            ['name' => 'Vue.js', 'category' => 'web-development', 'proficiency' => 'intermediate'],
        ] as $skill) {
            $student->skills()->updateOrCreate(['name' => $skill['name']], [...$skill, 'status' => 'verified']);
        }

        User::updateOrCreate(['email' => 'admin@utem.edu.my'], [
            'name' => 'Admin DigitalPassport',
            'password' => 'admin123',
            'role' => 'admin',
            'programme' => null,
            'matric_no' => null,
        ]);

        $assessments = [
            [
                'title' => 'JavaScript Fundamentals',
                'category' => 'programming',
                'description' => 'Test core JavaScript knowledge including variables, functions, the DOM, and JSON.',
                'questions' => [
                    ['question' => 'Which keyword declares a constant in JavaScript?', 'options' => ['var', 'let', 'const', 'static'], 'correct_option' => 2],
                    ['question' => 'Which method converts a JSON string into an object?', 'options' => ['JSON.stringify()', 'JSON.parse()', 'JSON.convert()', 'JSON.decode()'], 'correct_option' => 1],
                    ['question' => 'What does the strict equality operator compare?', 'options' => ['Value only', 'Type only', 'Value and type', 'Object names'], 'correct_option' => 2],
                ],
            ],
            [
                'title' => 'SQL and Database Basics',
                'category' => 'databases',
                'description' => 'Assess knowledge of SQL queries, table relationships, keys, and data filtering.',
                'questions' => [
                    ['question' => 'Which statement retrieves rows from a database?', 'options' => ['GET', 'FETCH', 'SELECT', 'RETRIEVE'], 'correct_option' => 2],
                    ['question' => 'What is the purpose of a primary key?', 'options' => ['Sort every query', 'Identify each row uniquely', 'Encrypt a table', 'Join every table'], 'correct_option' => 1],
                    ['question' => 'Which clause filters grouped results?', 'options' => ['WHERE', 'HAVING', 'ORDER BY', 'LIMIT'], 'correct_option' => 1],
                ],
            ],
        ];

        foreach ($assessments as $assessmentData) {
            $questions = $assessmentData['questions'];
            unset($assessmentData['questions']);
            $assessment = Assessment::updateOrCreate(
                ['title' => $assessmentData['title']],
                [...$assessmentData, 'time_limit_minutes' => 10, 'passing_score' => 60, 'is_published' => true],
            );
            $assessment->questions()->delete();
            foreach ($questions as $position => $question) {
                $assessment->questions()->create([...$question, 'points' => 1, 'position' => $position + 1]);
            }
        }

    }
}
