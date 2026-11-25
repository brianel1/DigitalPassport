<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Skills Profile</title>
    <style>
        @page { margin: 28px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .header { background: #172554; color: #fff; padding: 22px; }
        .eyebrow { color: #bfdbfe; font-size: 9px; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 5px 0 0; font-size: 24px; }
        h2 { border-bottom: 1px solid #dbe2ea; color: #172554; font-size: 13px; margin: 20px 0 8px; padding-bottom: 5px; }
        .muted { color: #64748b; }
        h2 { page-break-after: avoid; }
        .item { border: 1px solid #dbe2ea; margin-bottom: 7px; padding: 8px; page-break-inside: avoid; }
        .item strong { color: #172554; }
        .score { color: #1d4ed8; float: right; font-size: 14px; font-weight: bold; }
        .footer { border-top: 1px solid #dbe2ea; color: #64748b; margin-top: 20px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Skills Profile</div>
        <h1>{{ $user->name }}</h1>
        <div>{{ $user->matric_no }} | {{ $user->programme }}</div>
        @if ($user->profile?->headline)
            <div style="margin-top: 5px; color: #bfdbfe;">{{ $user->profile->headline }}</div>
        @endif
    </div>

    {{-- Stacked blocks instead of a two-column table: DomPDF cannot split a table row across pages, which produced blank pages for long lists. --}}
    <h2>Verified skills</h2>
    @forelse ($skills as $skill)
        <div class="item"><strong>{{ $skill->name }}</strong><span class="muted"> | {{ ucfirst($skill->proficiency) }}</span><br>{{ $skill->description }}@if($skill->reviewer)<br><span class="muted">Verified by {{ $skill->reviewer->name }} · {{ $skill->reviewed_at?->toDateString() }}</span>@endif</div>
    @empty
        <div class="muted">No verified skills recorded.</div>
    @endforelse

    <h2>Verified projects</h2>
    @forelse ($projects as $project)
        <div class="item"><strong>{{ $project->title }}</strong><br>{{ $project->description }}<br><span class="muted">{{ collect($project->technologies ?? [])->join(', ') }}</span>@if($project->reviewer)<br><span class="muted">Verified by {{ $project->reviewer->name }} · {{ $project->reviewed_at?->toDateString() }}</span>@endif</div>
    @empty
        <div class="muted">No verified projects recorded.</div>
    @endforelse

    <h2>Certifications</h2>
    @forelse ($certifications as $certification)
        <div class="item"><strong>{{ $certification->title }}</strong><span class="muted"> | {{ $certification->issuing_organization }} | {{ $certification->issue_date?->toDateString() }}</span>@if($certification->reviewer)<br><span class="muted">Verified by {{ $certification->reviewer->name }} · {{ $certification->reviewed_at?->toDateString() }}</span>@endif @if($certification->expiry_date)<br>Valid until {{ $certification->expiry_date->toDateString() }}@endif</div>
    @empty
        <div class="muted">No verified certifications recorded.</div>
    @endforelse

    <h2>Best passed assessment results</h2>
    @forelse ($attempts as $attempt)
        <div class="item"><strong>{{ $attempt->assessment?->title }}</strong><span class="score">{{ $attempt->percentage }}%</span><br><span class="muted">Completed {{ $attempt->submitted_at?->toDateString() }}</span></div>
    @empty
        <div class="muted">No passed assessments recorded.</div>
    @endforelse

    <div class="footer">Skills &amp; Career | Universiti Teknikal Malaysia Melaka</div>
</body>
</html>
