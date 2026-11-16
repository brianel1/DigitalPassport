<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 36px 32px; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 9.5px; line-height: 1.32; margin: 0; }
        .header { border-bottom: 1.5px solid #111827; padding-bottom: 10px; }
        h1 { font-size: 22px; letter-spacing: 0.5px; line-height: 1.05; margin: 0; text-transform: uppercase; }
        .headline { color: #374151; font-size: 10.5px; font-weight: bold; margin: 4px 0 0; }
        .contact { color: #374151; font-size: 8.5px; line-height: 1.45; margin: 0; }
        .layout { margin-top: 13px; }
        .section { page-break-inside: avoid; }
        .section + .section { margin-top: 13px; }
        h2 { border-bottom: 1px solid #111827; font-size: 9.5px; letter-spacing: 0.8px; line-height: 1.15; margin: 0 0 6px; padding-bottom: 3px; }
        h3 { font-size: 9.5px; line-height: 1.2; margin: 7px 0 1px; }
        p { margin: 0 0 5px; }
        ul { margin: 3px 0 0; padding-left: 14px; }
        li { margin: 0 0 3px; padding-left: 1px; }
        .meta { color: #4b5563; font-size: 8.5px; }
        .project { page-break-inside: avoid; }
        .project + .project { margin-top: 8px; }
        .skill-list li { margin-bottom: 3px; }
        .compact-list li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $content['personal']['name'] }}</h1>
        @if (!empty($content['personal']['headline']))
            <p class="headline">{{ $content['personal']['headline'] }}</p>
        @endif
        <p class="contact">{{ collect([$content['personal']['email'], $content['personal']['phone'], $content['personal']['location'], $content['personal']['website'] ?? ''])->filter()->join(' | ') }}</p>
    </header>

    <main class="layout">
                @if (!empty($content['summary']))
                    <section class="section">
                        <h2>SUMMARY</h2>
                        <p>{{ $content['summary'] }}</p>
                    </section>
                @endif

                @if (!empty($content['experience']))
                    <section class="section">
                        <h2>WORK EXPERIENCE</h2>
                        @foreach ($content['experience'] as $entry)
                            <div class="project">
                                <h3>{{ $entry['role'] }}</h3>
                                <p class="meta">{{ collect([$entry['company'], $entry['period']])->filter()->join(' | ') }}</p>
                                <p>{{ $entry['description'] }}</p>
                            </div>
                        @endforeach
                    </section>
                @endif
                @if (count($content['projects']))
                    <section class="section">
                        <h2>PROJECT EXPERIENCE</h2>
                        @foreach ($content['projects'] as $project)
                            <div class="project">
                                <h3>{{ $project['title'] }}</h3>
                                <p>{{ $project['description'] }}</p>
                                @if (count($project['technologies']))
                                    <p class="meta">Technologies: {{ collect($project['technologies'])->join(', ') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
                @if (count($content['skills']))
                    <section class="section">
                        <h2>SKILLS</h2>
                        <ul class="skill-list">
                            @foreach ($content['skills'] as $skill)
                                <li>{{ $skill }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (count($content['education']))
                    <section class="section">
                        <h2>EDUCATION</h2>
                        @foreach ($content['education'] as $education)
                            <h3>{{ $education['qualification'] }}</h3>
                            <p class="meta">{{ collect([$education['institution'], $education['period']])->filter()->join(' | ') }}</p>
                            @if(!empty($education['details']))<p>{{ $education['details'] }}</p>@endif
                        @endforeach
                    </section>
                @endif

                @if (count($content['certifications']))
                    <section class="section">
                        <h2>CERTIFICATIONS</h2>
                        <ul class="compact-list">
                            @foreach ($content['certifications'] as $certification)
                                <li>{{ collect([$certification['title'], $certification['issuer'], $certification['issueDate']])->filter()->join(' | ') }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (count($content['assessments']))
                    <section class="section">
                        <h2>ASSESSMENTS</h2>
                        <ul class="compact-list">
                            @foreach ($content['assessments'] as $assessment)
                                <li>{{ collect([$assessment['title'], $assessment['percentage'].'%', $assessment['completedAt']])->filter()->join(' | ') }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if (!empty($content['additional']))
                    <section class="section"><h2>ADDITIONAL INFORMATION</h2><p>{{ $content['additional'] }}</p></section>
                @endif
    </main>
</body>
</html>
