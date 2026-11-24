@php($doc = \App\Support\CvDocument::build($content))
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0.55in 0.6in; }
        body { color: #111111; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.25; margin: 0; }
        .name { font-size: 20pt; font-weight: bold; letter-spacing: 0.5pt; text-align: center; text-transform: uppercase; margin: 0; }
        .headline { font-size: 10.5pt; text-align: center; margin: 3pt 0 0; }
        .contact { color: #333333; font-size: 9pt; text-align: center; margin: 3pt 0 0; }
        h2 { border-bottom: 0.75pt solid #111111; font-size: 10.5pt; letter-spacing: 0.8pt; margin: 11pt 0 5pt; padding-bottom: 2pt; text-transform: uppercase; }
        p { margin: 0 0 3pt; }
        table.row { border-collapse: collapse; width: 100%; margin-top: 4pt; }
        table.row td { padding: 0; vertical-align: bottom; }
        .title { font-weight: bold; }
        .date { color: #333333; font-size: 9pt; text-align: right; white-space: nowrap; width: 30%; }
        .subtitle { color: #333333; font-style: italic; font-size: 9.5pt; margin: 1pt 0 1pt; }
        ul { margin: 2pt 0 0; padding-left: 16pt; }
        li { margin: 0 0 2pt; }
        .entry { page-break-inside: avoid; }
    </style>
</head>
<body>
    <p class="name">{{ $doc['name'] }}</p>
    @if ($doc['headline'] !== '')<p class="headline">{{ $doc['headline'] }}</p>@endif
    @if ($doc['contact'] !== '')<p class="contact">{{ $doc['contact'] }}</p>@endif

    @foreach ($doc['sections'] as $section)
        <h2>{{ $section['title'] }}</h2>
        @if ($section['paragraph'] !== '')<p>{{ $section['paragraph'] }}</p>@endif
        @foreach ($section['entries'] as $entry)
            <div class="entry">
                <table class="row"><tr><td class="title">{{ $entry['title'] }}</td>@if ($entry['date'] !== '')<td class="date">{{ $entry['date'] }}</td>@endif</tr></table>
                @if ($entry['subtitle'] !== '')<p class="subtitle">{{ $entry['subtitle'] }}</p>@endif
                @if ($entry['bullets'])<ul>@foreach ($entry['bullets'] as $bullet)<li>{{ $bullet }}</li>@endforeach</ul>@endif
            </div>
        @endforeach
        @if ($section['items'])<ul>@foreach ($section['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>@endif
    @endforeach
</body>
</html>
