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

        $path = $service->pdf($request->user());

        return response()->download($path, 'digital-passport.pdf')->deleteFileAfterSend(true);
    }
}
