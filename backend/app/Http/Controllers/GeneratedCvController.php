<?php

namespace App\Http\Controllers;

use App\Models\GeneratedCv;
use App\Services\CvExportService;
use App\Services\CvGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratedCvController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $items = $request->user()->generatedCvs()->latest()->get()->map(fn (GeneratedCv $cv) => $this->data($cv));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, CvGeneratorService $service): JsonResponse
    {
        $this->ensureStudent($request);
        return response()->json($this->data($service->generate($request->user())), 201);
    }

    public function update(Request $request, GeneratedCv $cv): JsonResponse
    {
        $this->ensureOwner($request, $cv);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'array'],
            'content.personal' => ['required', 'array'],
            'content.personal.name' => ['required', 'string', 'max:120'],
            'content.personal.email' => ['required', 'email', 'max:255'],
            'content.personal.phone' => ['nullable', 'string', 'max:40'],
            'content.personal.location' => ['nullable', 'string', 'max:120'],
            'content.personal.headline' => ['required', 'string', 'max:160'],
            'content.summary' => ['required', 'string', 'max:3000'],
            'content.skills' => ['present', 'array', 'max:50'],
            'content.skills.*' => ['string', 'max:100'],
            'content.education' => ['present', 'array', 'max:10'],
            'content.education.*.institution' => ['required', 'string', 'max:255'],
            'content.education.*.qualification' => ['required', 'string', 'max:255'],
            'content.education.*.period' => ['nullable', 'string', 'max:100'],
            'content.projects' => ['present', 'array', 'max:20'],
            'content.projects.*.title' => ['required', 'string', 'max:255'],
            'content.projects.*.description' => ['required', 'string', 'max:2000'],
            'content.projects.*.technologies' => ['present', 'array', 'max:30'],
            'content.projects.*.technologies.*' => ['string', 'max:100'],
            'content.certifications' => ['present', 'array', 'max:20'],
            'content.certifications.*.title' => ['required', 'string', 'max:255'],
            'content.certifications.*.issuer' => ['required', 'string', 'max:255'],
            'content.certifications.*.issueDate' => ['nullable', 'string', 'max:40'],
            'content.assessments' => ['present', 'array', 'max:20'],
            'content.assessments.*.title' => ['required', 'string', 'max:255'],
            'content.assessments.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'content.assessments.*.completedAt' => ['nullable', 'string', 'max:40'],
        ]);

        $cv->update(['title' => $validated['title'], 'content' => $validated['content']]);

        return response()->json($this->data($cv->refresh()));
    }

    public function export(Request $request, GeneratedCv $cv, string $format, CvExportService $service): BinaryFileResponse
    {
        $this->ensureOwner($request, $cv);
        abort_unless(in_array($format, ['pdf', 'word'], true), 404);

        $path = $format === 'pdf' ? $service->pdf($cv) : $service->word($cv);
        $extension = $format === 'pdf' ? 'pdf' : 'docx';
        $filename = str($cv->title)->slug().'.'.$extension;

        return response()->download($path, $filename)->deleteFileAfterSend(true);
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
        return [
            'id' => $cv->id,
            'title' => $cv->title,
            'content' => $cv->content,
            'version' => $cv->version,
            'status' => $cv->status,
            'createdAt' => $cv->created_at->toISOString(),
            'updatedAt' => $cv->updated_at->toISOString(),
        ];
    }
}
