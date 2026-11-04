<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Digital Skills Passport</title>
    <style>
        @page { margin: 28px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .header { background: #172554; color: #fff; padding: 22px; }
        .eyebrow { color: #bfdbfe; font-size: 9px; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 5px 0 0; font-size: 24px; }
        h2 { border-bottom: 1px solid #dbe2ea; color: #172554; font-size: 13px; margin: 20px 0 8px; padding-bottom: 5px; }
        .muted { color: #64748b; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; width: 50%; }
        .item { border: 1px solid #dbe2ea; margin-bottom: 7px; padding: 8px; }
        .item strong { color: #172554; }
        .score { color: #1d4ed8; float: right; font-size: 14px; font-weight: bold; }
        .footer { border-top: 1px solid #dbe2ea; color: #64748b; margin-top: 20px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Digital Skills Passport</div>
        <h1>{{ $user->name }}</h1>
        <div>{{ $user->matric_no }} | {{ $user->programme }}</div>
        @if ($user->profile?->headline)
            <div style="margin-top: 5px; color: #bfdbfe;">{{ $user->profile->headline }}</div>
        @endif
    </div>

    <table class="grid">
        <tr>
            <td style="padding-right: 8px;">
                <h2>Verified skills</h2>
                @forelse ($skills as $skill)
                    <div class="item"><strong>{{ $skill->name }}</strong><span class="muted"> | {{ ucfirst($skill->proficiency) }}</span><br>{{ $skill->description }}</div>
                @empty
                    <div class="muted">No verified skills recorded.</div>
                @endforelse
            </td>
            <td style="padding-left: 8px;">
                <h2>Verified projects</h2>
                @forelse ($projects as $project)
                    <div class="item"><strong>{{ $project->title }}</strong><br>{{ $project->description }}<br><span class="muted">{{ collect($project->technologies ?? [])->join(', ') }}</span></div>
                @empty
                    <div class="muted">No verified projects recorded.</div>
                @endforelse
            </td>
        </tr>
    </table>

    <h2>Certifications</h2>
    @forelse ($certifications as $certification)
        <div class="item"><strong>{{ $certification->title }}</strong><span class="muted"> | {{ $certification->issuing_organization }} | {{ $certification->issue_date?->toDateString() }}</span></div>
    @empty
        <div class="muted">No verified certifications recorded.</div>
    @endforelse

    <h2>Passed assessments</h2>
    @forelse ($attempts as $attempt)
        <div class="item"><strong>{{ $attempt->assessment?->title }}</strong><span class="score">{{ $attempt->percentage }}%</span><br><span class="muted">Completed {{ $attempt->submitted_at?->toDateString() }}</span></div>
    @empty
        <div class="muted">No passed assessments recorded.</div>
    @endforelse

    <div class="footer">DigitalPassport | Universiti Teknikal Malaysia Melaka</div>
</body>
</html>
