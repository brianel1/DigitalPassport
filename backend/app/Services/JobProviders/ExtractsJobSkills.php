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
            'C++', 'Golang', 'Kotlin', 'Swift', 'Flutter', 'Dart', 'Angular', 'Spring Boot',
            'Azure', 'Google Cloud', 'Kubernetes', 'MongoDB', 'Redis', 'Cybersecurity',
            'Power BI', 'Tableau', 'Excel', 'MATLAB', 'AutoCAD', 'SolidWorks', 'SAP',
            'Figma', 'Photoshop', 'SEO', 'Digital Marketing', 'Social Media',
            'Accounting', 'Bookkeeping', 'Financial Reporting', 'Taxation', 'Audit',
            'Project Management', 'Customer Service', 'Sales', 'Troubleshooting', 'Communication',
        ];

        return collect($skills)
            ->filter(fn ($skill) => preg_match('/(?<![\p{L}\p{N}])'.preg_quote($skill, '/').'(?![\p{L}\p{N}])/iu', $text))
            ->values()
            ->all();
    }
}
