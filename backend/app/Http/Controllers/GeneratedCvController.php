<?php

namespace App\Http\Controllers;

use App\Models\GeneratedCv;
use App\Models\JobListing;
use App\Services\CvExportService;
use App\Services\CvGeneratorService;
use App\Services\CvIntelligenceService;
use App\Services\CvTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratedCvController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $items = $request->user()->generatedCvs()->with('targetJob')->latest()->get()->map(fn (GeneratedCv $cv) => $this->data($cv));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, CvGeneratorService $service): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $request->validate(['title' => ['sometimes', 'required', 'string', 'max:160'], 'target_job_listing_id' => ['nullable', 'integer', 'exists:job_listings,id']]);
        $targetJob = isset($validated['target_job_listing_id']) ? JobListing::findOrFail($validated['target_job_listing_id']) : null;
        abort_if($targetJob && (! $targetJob->fetched_at || $targetJob->fetched_at->lt(now()->subDays(14)) || $targetJob->expires_at?->isPast()), 422, 'This listing is stale. Refresh job matches first.');
        set_time_limit(240);
        return response()->json($this->data($service->generate($request->user(), $targetJob, $validated['title'] ?? null)), 201);
    }

    public function update(Request $request, GeneratedCv $cv): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'array'],
            'content.personal' => ['required', 'array'],
            'content.personal.name' => ['present', 'nullable', 'string', 'max:120'],
            'content.personal.email' => ['present', 'nullable', 'email', 'max:255'],
            'content.personal.phone' => ['nullable', 'string', 'max:40'],
            'content.personal.location' => ['nullable', 'string', 'max:120'],
            'content.personal.headline' => ['present', 'nullable', 'string', 'max:160'],
            'content.personal.website' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content.summary' => ['present', 'nullable', 'string', 'max:3000'],
            'content.additional' => ['sometimes', 'nullable', 'string', 'max:22000'],
            'content.experience' => ['sometimes', 'array', 'max:20'],
            'content.experience.*.role' => ['present', 'nullable', 'string', 'max:255'],
            'content.experience.*.company' => ['present', 'nullable', 'string', 'max:255'],
            'content.experience.*.period' => ['present', 'nullable', 'string', 'max:100'],
            'content.experience.*.description' => ['present', 'nullable', 'string', 'max:2000'],
            'content.skills' => ['present', 'array', 'max:50'],
            'content.skills.*' => ['string', 'max:100'],
            'content.education' => ['present', 'array', 'max:10'],
            'content.education.*.institution' => ['present', 'nullable', 'string', 'max:255'],
            'content.education.*.qualification' => ['present', 'nullable', 'string', 'max:255'],
            'content.education.*.details' => ['nullable', 'string', 'max:2000'],
            'content.education.*.period' => ['nullable', 'string', 'max:100'],
            'content.projects' => ['present', 'array', 'max:20'],
            'content.projects.*.title' => ['present', 'nullable', 'string', 'max:255'],
            'content.projects.*.description' => ['present', 'nullable', 'string', 'max:2000'],
            'content.projects.*.technologies' => ['present', 'array', 'max:30'],
            'content.projects.*.technologies.*' => ['string', 'max:100'],
            'content.certifications' => ['present', 'array', 'max:20'],
            'content.certifications.*.title' => ['present', 'nullable', 'string', 'max:255'],
            'content.certifications.*.issuer' => ['present', 'nullable', 'string', 'max:255'],
            'content.certifications.*.issueDate' => ['nullable', 'string', 'max:40'],
            'content.assessments' => ['present', 'array', 'max:20'],
            'content.assessments.*.title' => ['required', 'string', 'max:255'],
            'content.assessments.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'content.assessments.*.completedAt' => ['nullable', 'string', 'max:40'],
        ], [], [
            'title' => 'document name', 'content.personal.name' => 'full name', 'content.personal.email' => 'contact email',
            'content.summary' => 'professional summary', 'content.projects.*.title' => 'project title',
            'content.projects.*.description' => 'project description', 'content.experience.*.description' => 'experience description',
            'content.education.*.institution' => 'institution', 'content.education.*.qualification' => 'qualification',
            'content.certifications.*.title' => 'certification title', 'content.certifications.*.issuer' => 'certification issuer',
        ]);

        // JSON arrays may otherwise retain unvalidated extra keys at their parent level.
        $content = $validated['content'];
        $fields = ['personal' => ['name', 'email', 'phone', 'location', 'headline', 'website'], 'education' => ['institution', 'qualification', 'period', 'details'], 'experience' => ['role', 'company', 'period', 'description'], 'projects' => ['title', 'description', 'technologies'], 'certifications' => ['title', 'issuer', 'issueDate'], 'assessments' => ['title', 'percentage', 'completedAt']];
        $clean = ['summary' => $content['summary'] ?? '', 'additional' => $content['additional'] ?? '', 'skills' => $content['skills']];
        foreach ($fields as $section => $keys) {
            $row = fn ($item) => array_map(fn ($value) => $value ?? '', array_intersect_key($item, array_flip($keys)));
            $clean[$section] = $section === 'personal' ? $row($content[$section]) : array_map($row, $content[$section] ?? []);
        }
        $clean['personal']['phone'] = \App\Support\PhoneNumber::display($clean['personal']['phone'] ?? '');
        $changed = $cv->content !== $clean;
        $cv->update(['title' => $validated['title'], 'content' => $clean, 'analysis' => $changed ? null : $cv->analysis]);

        return response()->json($this->data($cv->refresh()));
    }

    public function rename(Request $request, GeneratedCv $cv): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        $validated = $request->validate(['title' => ['required', 'string', 'max:160']], [], ['title' => 'document name']);
        $cv->update(['title' => $validated['title']]);
        return response()->json($this->data($cv->refresh()));
    }

    public function export(Request $request, GeneratedCv $cv, string $format, CvExportService $service): BinaryFileResponse
    {
        $this->ensureOwner($request, $cv);
        abort_unless(in_array($format, ['pdf', 'word'], true), 404);
        \Illuminate\Support\Facades\Validator::make($cv->content, ['personal.name' => ['required'], 'personal.email' => ['required', 'email']], [], ['personal.name' => 'full name', 'personal.email' => 'contact email'])->validate();

        try { $path = $format === 'pdf' ? $service->pdf($cv) : $service->word($cv); app(\App\Services\SystemEventLog::class)->record('export','completed','Document export completed.'); }
        catch (\Throwable $error) { app(\App\Services\SystemEventLog::class)->record('export','failed','Document export failed. Retry the export and inspect the server log if it repeats.'); throw $error; }
        $extension = $format === 'pdf' ? 'pdf' : 'docx';
        $filename = str($cv->title)->slug().'.'.$extension;

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function upload(Request $request, CvTextExtractor $extractor, CvIntelligenceService $intelligence): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $request->validate([
            'file' => ['required_without:text', 'prohibits:text', 'file', 'mimes:pdf,docx,txt', 'extensions:pdf,docx,txt', 'max:2048'],
            'text' => ['required_without:file', 'prohibits:file', 'string', 'min:50', 'max:20000'],
            'title' => ['sometimes', 'required', 'string', 'max:160'],
        ]);
        set_time_limit(150);
        $file = $request->file('file');
        $text = $file ? $extractor->extract($file) : $extractor->clean($validated['text']);
        $path = $file?->store('cv_sources/'.$request->user()->id, 'local');
        try {
            $cv = DB::transaction(function () use ($request, $file, $path, $text, $intelligence, $validated, $extractor) {
                $user = $request->user();
                $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();
                return $user->generatedCvs()->create([
                    'title' => $validated['title'] ?? Str::limit($user->name.' — Uploaded CV', 160, ''), 'content' => $intelligence->basic($text, $user),
                    'version' => ((int) $user->generatedCvs()->max('version')) + 1, 'status' => 'draft',
                    'source_type' => $file ? 'upload' : 'paste', 'source_filename' => $file ? Str::limit(basename($file->getClientOriginalName()), 255, '') : 'Pasted CV text',
                    'source_path' => $path, 'source_text' => $text, 'model_name' => $extractor->method === 'local_ocr' ? 'local-ocr-v1' : 'text-extraction-v1',
                    'provenance' => ['method' => $file ? $extractor->method : 'pasted_text', 'original_text' => $text, 'extracted_at' => now()->toISOString()],
                ]);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return response()->json($this->data($cv), 201);
    }

    public function structure(Request $request, GeneratedCv $cv, CvTextExtractor $extractor, CvIntelligenceService $intelligence): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        abort_unless($cv->source_type !== 'profile', 422, 'Choose an uploaded CV first.');
        $validated = $request->validate(['source_text' => ['required', 'string', 'min:50', 'max:20000']]);
        $text = $extractor->clean($validated['source_text']);
        $lock = Cache::lock('cv-ai:'.$cv->id, 150);
        abort_unless($lock->get(), 409, 'This CV is already being processed. Please wait.');
        try {
            set_time_limit(240);
            [$content, $engine] = $intelligence->structure($text, $request->user());
            $this->persistAiResult($cv, ['source_text' => $text, 'content' => $content, 'model_name' => $engine, 'analysis' => null, 'provenance' => [...($cv->provenance ?? []), 'structured_at' => now()->toISOString(), 'structure_model' => $engine, 'structure_method' => 'section-grounded-source-v2']]);
        } finally { $lock->release(); }
        return response()->json($this->data($cv->refresh()));
    }

    public function review(Request $request, GeneratedCv $cv, CvIntelligenceService $intelligence): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        $lock = Cache::lock('cv-ai:'.$cv->id, 240);
        abort_unless($lock->get(), 409, 'This CV is already being processed. Please wait.');
        try {
            set_time_limit(240);
            $analysis = $intelligence->review($cv);
            $this->persistAiResult($cv, ['analysis' => $analysis, 'provenance' => [...($cv->provenance ?? []), 'reviewed_at' => now()->toISOString(), 'review_model' => $analysis['engine'], 'review_method' => 'grounded-review-v1']]);
        } finally { $lock->release(); }
        return response()->json($this->data($cv->refresh()));
    }

    public function duplicate(Request $request, GeneratedCv $cv): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        $validated = $request->validate(['target_job_listing_id' => ['nullable', 'integer', 'exists:job_listings,id']]);
        $job = isset($validated['target_job_listing_id']) ? JobListing::findOrFail($validated['target_job_listing_id']) : null;
        abort_if($job && (! $job->fetched_at || $job->fetched_at->lt(now()->subDays(14)) || $job->expires_at?->isPast()), 422, 'This listing is no longer current. Choose another job match.');
        $copy = DB::transaction(function () use ($request, $cv, $job) {
            $user = $request->user();
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();
            // Source files belong to the original record; copies keep text, not a shared deletion path.
            $copy = $cv->replicate(['source_path', 'analysis']);
            $copy->version = ((int) $user->generatedCvs()->max('version')) + 1;
            $copy->target_job_listing_id = $job?->id;
            $copy->title = Str::limit($user->name.($job ? ' — '.$job->title : ' — CV copy'), 160, '');
            $copy->save();
            return $copy;
        });
        return response()->json($this->data($copy), 201);
    }

    public function source(Request $request, GeneratedCv $cv): BinaryFileResponse
    {
        $this->ensureOwner($request, $cv);
        abort_unless($cv->source_path && Storage::disk('local')->exists($cv->source_path), 404);
        return response()->download(Storage::disk('local')->path($cv->source_path), $cv->source_filename, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Request $request, GeneratedCv $cv): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        if ($cv->source_path) Storage::disk('local')->delete($cv->source_path);
        $cv->delete();
        return response()->json(['message' => 'CV and its uploaded source removed.']);
    }

    private function persistAiResult(GeneratedCv $cv, array $changes): void
    {
        DB::transaction(function () use ($cv, $changes) {
            $current = GeneratedCv::whereKey($cv->id)->lockForUpdate()->firstOrFail();
            abort_if($current->content !== $cv->content || $current->source_text !== $cv->source_text, 409, 'This CV changed while AI was working. Reload the latest version and try again.');
            $current->update($changes);
        });
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    private function ensureOwner(Request $request, GeneratedCv $cv): void
    {
        $this->ensureStudent($request);
        abort_unless($cv->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function data(GeneratedCv $cv): array
    {
        $content = $cv->content;
        $content['personal']['phone'] = \App\Support\PhoneNumber::display($content['personal']['phone'] ?? '');
        return [
            'id' => $cv->id,
            'title' => $cv->title,
            'content' => $content,
            'version' => $cv->version,
            'status' => $cv->status,
            'modelName' => $cv->model_name,
            'sourceType' => $cv->source_type ?? 'profile',
            'sourceFilename' => $cv->source_filename,
            'sourceText' => $cv->source_text,
            'hasSourceFile' => (bool) $cv->source_path,
            'analysis' => $cv->analysis,
            'extractionMethod' => $cv->provenance['method'] ?? null,
            'targetJob' => $cv->targetJob ? ['id' => $cv->targetJob->id, 'title' => $cv->targetJob->title, 'company' => $cv->targetJob->company] : null,
            'createdAt' => $cv->created_at->toISOString(),
            'updatedAt' => $cv->updated_at->toISOString(),
        ];
    }
}
