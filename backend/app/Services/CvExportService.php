<?php

namespace App\Services;

use App\Models\GeneratedCv;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;

class CvExportService
{
    public function pdf(GeneratedCv $cv): string
    {
        $path = $this->path($cv, 'pdf');
        Storage::disk('local')->put($path, Pdf::loadView('cv.ats', ['content' => $cv->content])->setPaper('a4')->output());

        return Storage::disk('local')->path($path);
    }

    public function word(GeneratedCv $cv): string
    {
        $content = $cv->content;
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);
        $section = $phpWord->addSection([
            'marginTop' => Converter::inchToTwip(0.5),
            'marginBottom' => Converter::inchToTwip(0.5),
            'marginLeft' => Converter::inchToTwip(0.6),
            'marginRight' => Converter::inchToTwip(0.6),
        ]);

        $personal = $content['personal'];
        $section->addText(strtoupper($personal['name']), ['bold' => true, 'size' => 20], ['spaceAfter' => 30]);
        if (! empty($personal['headline'])) {
            $section->addText($personal['headline'], ['bold' => true, 'size' => 10, 'color' => '374151'], ['spaceAfter' => 30]);
        }
        $contact = collect([$personal['email'], $personal['phone'], $personal['location']])->filter()->join(' | ');
        $section->addText($contact, ['size' => 8, 'color' => '374151'], ['spaceAfter' => 80, 'borderBottomSize' => 8, 'borderBottomColor' => '111827']);

        $section->addText('', [], ['spaceAfter' => 30]);
        $columns = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 0,
            'cellSpacing' => 0,
        ]);
        $columns->addRow();
        $left = $columns->addCell(Converter::inchToTwip(4.4), ['valign' => 'top', 'borderSize' => 0, 'cellMarginRight' => 120]);
        $right = $columns->addCell(Converter::inchToTwip(2.25), ['valign' => 'top', 'borderSize' => 0, 'borderLeftSize' => 6, 'borderLeftColor' => 'D1D5DB', 'cellMarginLeft' => 180]);

        if (! empty($content['summary'])) {
            $this->heading($left, 'SUMMARY');
            $left->addText($content['summary'], [], ['spaceAfter' => 100]);
        }

        if (! empty($content['projects'])) {
            $this->heading($left, 'PROJECT EXPERIENCE');
            foreach ($content['projects'] as $project) {
                $left->addText($project['title'], ['bold' => true, 'size' => 9], ['spaceBefore' => 55, 'spaceAfter' => 20]);
                $left->addText($project['description'], [], ['spaceAfter' => 20]);
                if (! empty($project['technologies'])) {
                    $left->addText('Technologies: '.collect($project['technologies'])->join(', '), ['italic' => true, 'size' => 8, 'color' => '4B5563'], ['spaceAfter' => 80]);
                }
            }
        }

        if (! empty($content['skills'])) {
            $this->heading($right, 'SKILLS');
            foreach ($content['skills'] as $skill) {
                $right->addListItem($skill, 0, ['size' => 8.5], ['spaceAfter' => 25]);
            }
        }

        if (! empty($content['education'])) {
            $this->heading($right, 'EDUCATION');
            foreach ($content['education'] as $education) {
                $right->addText($education['qualification'], ['bold' => true, 'size' => 9], ['spaceAfter' => 20]);
                $right->addText(collect([$education['institution'], $education['period']])->filter()->join(' | '), ['size' => 8, 'color' => '4B5563'], ['spaceAfter' => 80]);
            }
        }

        if (! empty($content['certifications'])) {
            $this->heading($right, 'CERTIFICATIONS');
            foreach ($content['certifications'] as $certification) {
                $right->addListItem(collect([$certification['title'], $certification['issuer'], $certification['issueDate']])->filter()->join(' | '), 0, ['size' => 8.5], ['spaceAfter' => 35]);
            }
        }

        if (! empty($content['assessments'])) {
            $this->heading($right, 'ASSESSMENTS');
            foreach ($content['assessments'] as $assessment) {
                $right->addListItem(collect([$assessment['title'], $assessment['percentage'].'%', $assessment['completedAt']])->filter()->join(' | '), 0, ['size' => 8.5], ['spaceAfter' => 35]);
            }
        }

        $path = $this->path($cv, 'docx');
        $absolutePath = Storage::disk('local')->path($path);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }
        $phpWord->save($absolutePath, 'Word2007');

        return $absolutePath;
    }

    private function heading($container, string $title): void
    {
        $container->addText($title, ['bold' => true, 'size' => 9, 'color' => '111827'], [
            'borderBottomSize' => 8,
            'borderBottomColor' => '111827',
            'spaceBefore' => 90,
            'spaceAfter' => 60,
        ]);
    }

    private function path(GeneratedCv $cv, string $extension): string
    {
        return 'cv_exports/'.$cv->user_id.'/'.Str::slug($cv->title).'-'.Str::random(8).'.'.$extension;
    }
}
