<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $certifications = $request->user()->certifications()->with('evidenceFiles')->latest()->get();

        return response()->json(['data' => $certifications->map(fn (Certification $item) => $this->certificationData($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'issuing_organization' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'credential_id' => ['nullable', 'string', 'max:120'],
            'credential_url' => ['nullable', 'url', 'max:255'],
            'evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $certification = $request->user()->certifications()->create(collect($validated)->except('evidence')->all());
        $file = $request->file('evidence');
        $certification->evidenceFiles()->create([
            'user_id' => $request->user()->id,
            'disk' => 'local',
            'path' => $file->store("evidence/certifications/{$certification->id}"),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return response()->json($this->certificationData($certification->refresh()->load('evidenceFiles')), 201);
    }

    public function destroy(Request $request, Certification $certification): JsonResponse
    {
        $this->ensureOwner($request, $certification);
        foreach ($certification->evidenceFiles as $evidence) {
            Storage::disk($evidence->disk)->delete($evidence->path);
            $evidence->delete();
        }
        $certification->delete();

        return response()->json(['message' => 'Certification deleted successfully.']);
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    private function ensureOwner(Request $request, Certification $certification): void
    {
        $this->ensureStudent($request);
        abort_unless($certification->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function certificationData(Certification $certification): array
    {
        return [
            'id' => $certification->id,
            'title' => $certification->title,
            'issuingOrganization' => $certification->issuing_organization,
            'issueDate' => $certification->issue_date->toDateString(),
            'expiryDate' => $certification->expiry_date?->toDateString(),
            'referenceNumber' => $certification->credential_id,
            'evidenceFiles' => $certification->evidenceFiles->pluck('original_name')->values(),
            'status' => $certification->status,
            'linkedSkills' => [],
            'adminRemarks' => $certification->admin_remarks,
            'createdAt' => $certification->created_at->toISOString(),
            'updatedAt' => $certification->updated_at->toISOString(),
        ];
    }
}
