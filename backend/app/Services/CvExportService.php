<?php

namespace App\Services;

use App\Models\GeneratedCv;
use App\Support\CvDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Tab;

class CvExportService
{
    public function pdf(GeneratedCv $cv): string
    {
        $path = $this->path($cv, 'pdf');
        Storage::disk('local')->put($path, Pdf::loadView('cv.ats', ['content' => $cv->content])->setPaper('a4')->output());

        return Storage::disk('local')->path($path);
    }

    /** Mirrors resources/views/cv/ats.blade.php: same A4 margins, fonts, sizes, order and bullets. */
    public function word(GeneratedCv $cv): string
    {
        $doc = CvDocument::build($cv->content);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => 1.15]);
        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => Converter::inchToTwip(0.55), 'marginBottom' => Converter::inchToTwip(0.55),
            'marginLeft' => Converter::inchToTwip(0.6), 'marginRight' => Converter::inchToTwip(0.6),
        ]);
        $textWidth = (int) round(Converter::inchToTwip(8.27 - 1.2));
        $gray = '333333';
        $phpWord->addNumberingStyle('cvBullet', ['type' => 'singleLevel', 'levels' => [['format' => 'bullet', 'text' => '•', 'left' => 320, 'hanging' => 200, 'tabPos' => 320]]]);

        $section->addText(mb_strtoupper($doc['name']), ['bold' => true, 'size' => 20], ['alignment' => 'center']);
        if ($doc['headline'] !== '') $section->addText($doc['headline'], ['size' => 10.5], ['alignment' => 'center', 'spaceBefore' => 60]);
        if ($doc['contact'] !== '') $section->addText($doc['contact'], ['size' => 9, 'color' => $gray], ['alignment' => 'center', 'spaceBefore' => 60]);

        foreach ($doc['sections'] as $part) {
            $section->addText(mb_strtoupper($part['title']), ['bold' => true, 'size' => 10.5, 'spacing' => 16], [
                'spaceBefore' => 240, 'spaceAfter' => 100, 'borderBottomSize' => 6, 'borderBottomColor' => '111111',
            ]);
            if ($part['paragraph'] !== '') $section->addText($part['paragraph'], [], ['spaceAfter' => 60]);
            foreach ($part['entries'] as $entry) {
                $row = $section->addTextRun(['spaceBefore' => 100, 'keepNext' => true, 'tabs' => [new Tab('right', $textWidth)]]);
                $row->addText($entry['title'], ['bold' => true]);
                if ($entry['date'] !== '') $row->addText("\t".$entry['date'], ['size' => 9, 'color' => $gray]);
                if ($entry['subtitle'] !== '') $section->addText($entry['subtitle'], ['italic' => true, 'size' => 9.5, 'color' => $gray], ['spaceBefore' => 20, 'keepNext' => (bool) $entry['bullets']]);
                foreach ($entry['bullets'] as $bullet) $section->addListItem($bullet, 0, [], 'cvBullet', ['spaceBefore' => 40]);
            }
            foreach ($part['items'] as $item) $section->addListItem($item, 0, [], 'cvBullet', ['spaceBefore' => 40]);
        }

        $path = $this->path($cv, 'docx');
        $absolutePath = Storage::disk('local')->path($path);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }
        $phpWord->save($absolutePath, 'Word2007');

        return $absolutePath;
    }

    private function path(GeneratedCv $cv, string $extension): string
    {
        return 'cv_exports/'.$cv->user_id.'/'.Str::slug($cv->title).'-'.Str::random(8).'.'.$extension;
    }
}
