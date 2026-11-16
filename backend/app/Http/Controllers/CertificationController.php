<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CertificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $certifications = $request->user()->certifications()->with(['evidenceFiles', 'skills', 'reviewer'])->latest()->get();

        return response()->json(['data' => $certifications->map(fn (Certification $item) => $this->certificationData($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        return $this->saveRecord($request, new Certification(), 201);
    }

    public function update(Request $request, Certification $certification): JsonResponse
    {
        $this->ensureOwner($request, $certification);
        return $this->saveRecord($request, $certification, 200);
    }

    private function saveRecord(Request $request, Certification $record, int $status): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'issuing_organization' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'credential_id' => ['nullable', 'string', 'max:120'],
            'credential_url' => ['nullable', 'url', 'max:255'],
            'evidence' => [$record->exists && $record->evidenceFiles()->exists() ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')->where('user_id', $request->user()->id)],
        ]);
        $record = app(\App\Services\EvidenceWorkflow::class)->save($request, $record, $validated);
        return response()->json($this->certificationData($record), $status);
    }

    public function destroy(Request $request, Certification $certification): JsonResponse
    {
        $this->ensureOwner($request, $certification);
        foreach ($certification->evidenceFiles as $evidence) {
            Storage::disk($evidence->disk)->delete($evidence->path);
            $evidence->delete();
        }
        $linked = $certification->skills()->pluck('skills.id')->all();
        $certification->delete();
        app(\App\Services\EvidenceWorkflow::class)->invalidateUnsupportedSkills($linked);

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
            ...app(\App\Services\EvidenceWorkflow::class)->details($certification),
            'id' => $certification->id,
            'title' => $certification->title,
            'issuingOrganization' => $certification->issuing_organization,
            'issueDate' => $certification->issue_date->toDateString(),
            'expired' => $certification->expiry_date?->lt(today()) ?? false,
            'credentialUrl' => $certification->credential_url,
            'expiryDate' => $certification->expiry_date?->toDateString(),
            'referenceNumber' => $certification->credential_id,
            'evidenceFiles' => $certification->evidenceFiles->pluck('original_name')->values(),
            'status' => $certification->status,
            'linkedSkills' => $certification->skills->pluck('id')->values(),
            'adminRemarks' => $certification->admin_remarks,
            'createdAt' => $certification->created_at->toISOString(),
            'updatedAt' => $certification->updated_at->toISOString(),
        ];
    }
}
