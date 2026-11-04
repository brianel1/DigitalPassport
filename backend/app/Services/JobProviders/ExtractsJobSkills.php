<?php

namespace App\Services\JobProviders;

trait ExtractsJobSkills
{
    /** @return array<int, string> */
    private function extractSkills(string $text): array
    {
        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Vue.js', 'React', 'MySQL',
            'PostgreSQL', 'SQL', 'REST API', 'Git', 'Python', 'Java', 'C#', '.NET',
            'Docker', 'AWS', 'Linux', 'HTML', 'CSS', 'Tailwind CSS', 'Node.js',
            'Troubleshooting', 'Communication',
        ];

        return collect($skills)
            ->filter(fn ($skill) => str_contains(mb_strtolower($text), mb_strtolower($skill)))
            ->values()
            ->all();
    }
}
