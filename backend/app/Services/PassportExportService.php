<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PassportExportService
{
    public function pdf(User $user): string
    {
        $user->load(['profile', 'skills', 'projects', 'certifications']);
        $attempts = AssessmentAttempt::query()
            ->with('assessment')
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->latest('submitted_at')
            ->get();

        $path = 'passport_exports/'.$user->id.'/'.Str::slug($user->name).'-'.Str::random(8).'.pdf';
        $pdf = Pdf::loadView('passport.digital', [
            'user' => $user,
            'skills' => $user->skills->where('status', 'verified'),
            'projects' => $user->projects->where('status', 'verified'),
            'certifications' => $user->certifications->where('status', 'verified'),
            'attempts' => $attempts,
        ])->setPaper('a4');

        Storage::disk('local')->put($path, $pdf->output());

        return Storage::disk('local')->path($path);
    }
}
