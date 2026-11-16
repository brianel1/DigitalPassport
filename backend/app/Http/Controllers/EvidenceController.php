<?php

namespace App\Http\Controllers;

use App\Models\EvidenceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function show(Request $request, EvidenceFile $file)
    {
        abort_unless($request->user()->role === 'admin' || ($request->user()->role === 'student' && $file->user_id === $request->user()->id), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404, 'This evidence file is no longer available. Please upload it again.');
        $headers = ['Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($request->boolean('download')) return Storage::disk($file->disk)->download($file->path, $file->original_name, $headers);
        abort_unless(in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png']), 415);
        return response()->file(Storage::disk($file->disk)->path($file->path), $headers);
    }
}
