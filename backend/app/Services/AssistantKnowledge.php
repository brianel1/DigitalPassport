<?php

namespace App\Services;

/** Small, reviewed reference notes retrieved by topic, not a second intent gate. */
class AssistantKnowledge
{
    public function relevant(string $question): string
    {
        $notes=[];
        foreach ([
            '/\bats\b/iu'=>'ATS stands for Applicant Tracking System. Employers use it to store, organise and track applications. Parsing and automatic screening capabilities vary across products and employers. Clear headings and text help parsing; no format guarantees a pass.',
            '/part.?time|kerja sambilan|pengalaman keje|no work experience|no achievements|xde pengalaman|takde pengalaman/iu'=>'Part-time employment belongs in Work experience, with its real job title, employer and dates. Label it part-time when useful. Students without paid work can use academic projects, volunteering, responsibilities and real skills. Lacking work experience does not mean they failed to link evidence. Do not invent numbers. A truthful qualitative contribution is enough.',
            '/fit score|skor|padanan/iu'=>'The fit score measures listed skill requirements supported by current verified records, with comparable location, employment-type and salary preferences affecting the result. Uploaded CV claims alone add no points. Explain missing verified skills and preferences, without percentages or a formula unless asked. 100 means full listed coverage under these checks, NOT a job guarantee. Exact personal reasons require a specific listing/result; do not claim to see one when none is supplied.',
            '/\bsql\b/iu'=>'SQL basics: tables contain rows and columns. SELECT retrieves data; WHERE filters rows; ORDER BY sorts results. GROUP BY groups rows for aggregate functions; JOIN combines related tables. A small sample table lets a beginner practise each operation. SQL is a query language.',
            '/internship|latihan industri|kerja tetap/iu'=>'Internship is a time-limited learning placement; duration and pay vary by employer and programme. Permanent work has no predetermined end date. Full-time describes working hours, not contract permanence. A fresh graduate can apply for permanent jobs too.',
            '/interview|temu ?duga|nervous/iu'=>'For nerves: suggest preparing a short introduction, one real project example and practical arrangements; offer one practice question. For project answers, use problem, your contribution and what you learned. Do not create pretend projects, responsibilities, employers or metrics; use [project], [your contribution] placeholders if no facts were supplied.',
        ] as $pattern=>$note)if(preg_match($pattern,$question))$notes[]=$note;
        return implode("\n",$notes);
    }
}
