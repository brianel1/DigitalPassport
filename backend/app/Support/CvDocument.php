<?php

namespace App\Support;

/**
 * One ATS layout shared by the PDF and Word exports (and mirrored by the web preview),
 * so every format shows the same sections, order, wording and bullets.
 */
class CvDocument
{
    /** @return array{name: string, headline: string, contact: string, sections: array<int, array<string, mixed>>} */
    public static function build(array $content): array
    {
        $personal = $content['personal'] ?? [];
        $sections = [];
        $add = function (string $title, array $section) use (&$sections) {
            if (array_filter($section)) $sections[] = ['title' => $title, 'paragraph' => '', 'entries' => [], 'items' => [], ...$section];
        };

        $add('Professional Summary', ['paragraph' => trim($content['summary'] ?? '')]);
        $add('Skills', ['paragraph' => implode(', ', array_filter($content['skills'] ?? []))]);
        $add('Work Experience', ['entries' => array_map(fn ($row) => self::entry($row['role'] ?? '', $row['period'] ?? '', $row['company'] ?? '', $row['description'] ?? ''), $content['experience'] ?? [])]);
        $add('Projects', ['entries' => array_map(fn ($row) => self::entry($row['title'] ?? '', '', ($row['technologies'] ?? []) ? 'Technologies: '.implode(', ', $row['technologies']) : '', $row['description'] ?? ''), $content['projects'] ?? [])]);
        $add('Education', ['entries' => array_map(fn ($row) => self::entry($row['qualification'] ?? '', $row['period'] ?? '', $row['institution'] ?? '', $row['details'] ?? ''), $content['education'] ?? [])]);
        $add('Certifications', ['entries' => array_map(fn ($row) => self::entry($row['title'] ?? '', $row['issueDate'] ?? '', $row['issuer'] ?? '', ''), $content['certifications'] ?? [])]);
        $add('Verified Assessments', ['items' => array_map(fn ($row) => trim(($row['title'] ?? '').' — '.rtrim(rtrim(number_format((float) ($row['percentage'] ?? 0), 1), '0'), '.').'%'.(!empty($row['completedAt']) ? ' ('.$row['completedAt'].')' : '')), $content['assessments'] ?? [])]);
        $add('Additional Information', ['items' => self::lines($content['additional'] ?? '')]);

        return [
            'name' => trim($personal['name'] ?? ''),
            'headline' => trim($personal['headline'] ?? ''),
            'contact' => implode(' | ', array_filter(array_map('trim', [$personal['email'] ?? '', $personal['phone'] ?? '', $personal['location'] ?? '', $personal['website'] ?? '']))),
            'sections' => array_values(array_filter($sections, fn ($section) => $section['paragraph'] !== '' || $section['entries'] || $section['items'])),
        ];
    }

    /** @return array{title: string, date: string, subtitle: string, bullets: array<int, string>} */
    private static function entry(string $title, string $date, string $subtitle, string $description): array
    {
        return ['title' => trim($title), 'date' => trim($date), 'subtitle' => trim($subtitle), 'bullets' => self::bullets($description)];
    }

    /** Lines become bullets; a single paragraph with several sentences is split into one bullet per sentence. */
    public static function bullets(string $text): array
    {
        $lines = self::lines($text);
        if (count($lines) === 1) {
            $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z])/u', $lines[0]) ?: [];
            if (count($sentences) > 1) return array_values(array_filter(array_map('trim', $sentences)));
        }
        return $lines;
    }

    /** @return array<int, string> */
    private static function lines(string $text): array
    {
        return array_values(array_filter(array_map(fn ($line) => trim(preg_replace('/^[\s•●▪*\-–]+/u', '', $line)), preg_split('/\R/u', $text) ?: [])));
    }
}
