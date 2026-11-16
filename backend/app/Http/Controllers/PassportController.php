<?php

namespace App\Http\Controllers;

use App\Services\PassportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PassportController extends Controller
{
    public function export(Request $request, PassportExportService $service): BinaryFileResponse
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');

        try { $path = $service->pdf($request->user()); app(\App\Services\SystemEventLog::class)->record('export','completed','Document export completed.'); }
        catch (\Throwable $error) { app(\App\Services\SystemEventLog::class)->record('export','failed','Document export failed. Retry the export and inspect the server log if it repeats.'); throw $error; }

        return response()->download($path, 'skills-profile.pdf')->deleteFileAfterSend(true);
    }
}
