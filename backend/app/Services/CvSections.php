<?php

namespace App\Services;

/** Keep source boundaries before asking a model to identify individual entries. */
class CvSections
{
    public function clean(string $text): string
    {
        $text = preg_replace('/[\p{Cf}\x{00a0}]/u', ' ', $text) ?? $text;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return implode("\n", array_map(fn ($line) => trim(preg_replace('/[\t ]+/u', ' ', $line)), explode("\n", $text)));
    }

    public function split(string $text): array
    {
        $sections = ['header' => ''];
        $current = 'header';
        $aliases = [
            'summary' => 'summary|profile|objective|career objective|professional summary|personal summary|professional profile|about me|ringkasan|profil diri|ringkasan profesional',
            'education' => 'education|academic qualifications|educational background|academic background|pendidikan|kelayakan akademik',
            'experience' => 'experience|work experience|working experience|professional experience|employment|employment history|work history|pengalaman kerja',
            'projects' => 'projects|project|project experience|academic projects|personal projects|projek|pengalaman projek',
            'certifications' => 'certifications|certificates|certification|licenses and certifications|sijil|pensijilan',
            'skills' => 'skills|technical skills|key skills|core competencies|kemahiran|kemahiran teknikal',
            'activities' => 'activities|activities and involvement|extracurricular activities|leadership and activities|volunteering|volunteer experience|aktiviti',
            'achievements' => 'achievements|awards|honours|honors|awards and achievements|pencapaian',
            'publications' => 'publications|research publications|penerbitan',
            'languages' => 'languages|language|bahasa',
            'references' => 'references|referees|rujukan',
        ];
        foreach (explode("\n", $this->clean($text)) as $line) {
            if ($line === '') continue;
            foreach ($aliases as $key => $pattern) {
                if (preg_match('/^(?:'.$pattern.')\s*[:|]?$/iu', $line)) {
                    $current = $key;
                    $sections[$key] ??= '';
                    continue 2;
                }
            }
            // Some exports put the skills heading and the first list on one line.
            if (preg_match('/^(?:technical skills|skills|kemahiran)\s*:\s*(.+)$/iu', $line, $m)) {
                $current = 'skills'; $line = $m[1]; $sections[$current] ??= '';
            }
            $sections[$current] .= ($sections[$current] ? "\n" : '').$line;
        }
        return $sections;
    }

    public function skills(string $body): array
    {
        $skills = [];
        foreach (explode("\n", $body) as $line) {
            $line = preg_replace('/^[\s●•▪*-]+/u', '', $line);
            $line = preg_replace('/^[^:]{1,60}:\s*/u', '', $line);
            foreach (preg_split('/[,;|•●](?![^()]*\))/u', $line) as $skill) {
                $skill = trim($skill);
                if ($skill !== '' && mb_strlen($skill) <= 100) $skills[] = $skill;
            }
        }
        return array_slice(array_values(array_unique($skills)), 0, 50);
    }
}
