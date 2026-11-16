<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

class CvTextExtractor
{
    public string $method = 'text';
    public function extract(UploadedFile $file): string
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $text = match ($extension) {
                'pdf' => $this->pdf($file->getRealPath()),
                'docx' => $this->docx($file->getRealPath()),
                'txt' => file_get_contents($file->getRealPath()),
                default => throw new \RuntimeException('Unsupported file.'),
            };
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw ValidationException::withMessages(['file' => 'This document could not be read. Use an unlocked PDF, a DOCX file, or paste your CV text.']);
        }
        return $this->clean($text);
    }

    public function clean(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';
        $text = app(CvSections::class)->clean($text);
        $text = trim(preg_replace('/[ \t]+/u', ' ', str_replace("\r", '', $text)) ?? '');
        if (mb_strlen($text) < 50) {
            throw ValidationException::withMessages(['file' => 'No readable CV text was found. Try a clearer scan, a text-based PDF / DOCX, or paste your CV text.']);
        }
        if (mb_strlen($text) > 20000) {
            throw ValidationException::withMessages(['file' => 'This document is too long. Upload a CV with at most 20,000 characters of text.']);
        }
        return $text;
    }

    private function pdf(string $path): string
    {
        $config = new \Smalot\PdfParser\Config();
        $config->setDecodeMemoryLimit(16 * 1024 * 1024);
        $document = (new Parser([], $config))->parseFile($path);
        if (count($document->getPages()) > 10) {
            throw ValidationException::withMessages(['file' => 'Upload a CV with no more than 10 pages.']);
        }
        $pages = $document->getPages();
        if (collect($pages)->contains(fn ($page) => mb_strlen(trim($page->getText())) < 50)) {
            $result = app(CvOcr::class)->extract($path);
            $this->method = $result['ocrPages'] > 0 ? 'local_ocr' : 'pdf_text';
            return $result['text'];
        }
        $this->method = 'pdf_text';
        return $document->getText();
    }

    private function docx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new \RuntimeException('Invalid DOCX.');
        try {
            $stat = $zip->statName('word/document.xml');
            if (! $stat || $stat['size'] > 2 * 1024 * 1024) throw new \RuntimeException('Invalid document size.');
            $xml = $zip->getFromName('word/document.xml');
            if (! is_string($xml) || stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) throw new \RuntimeException('Invalid XML.');
            $previous = libxml_use_internal_errors(true);
            try {
                $doc = new \DOMDocument();
                if (! $doc->loadXML($xml, LIBXML_NONET)) throw new \RuntimeException('Invalid XML.');
                $xpath = new \DOMXPath($doc);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $lines = [];
                foreach ($xpath->query('//w:p') as $paragraph) {
                    $parts = [];
                    foreach ($xpath->query('.//w:t', $paragraph) as $node) $parts[] = $node->textContent;
                    $lines[] = implode('', $parts);
                }
                return implode("\n", $lines);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        } finally {
            $zip->close();
        }
    }
}
