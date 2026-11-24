<?php

namespace App\Services\JobProviders;

use App\Contracts\JobProvider;
use App\Services\JobCatalogue;
use App\Support\MalaysiaLocation;
use Illuminate\Support\Str;
use RuntimeException;

class PublicHtmlJobProvider implements JobProvider
{
    private array $notices = [];
    public function __construct(private readonly string $name) {}
    public function source(): string { return $this->name; }
    public function isConfigured(): bool { return in_array($this->name, config('services.public_jobs.sources', []), true); }
    public function warnings(): array { return array_values(array_unique($this->notices)); }

    public function fetch(string $keywords, string $location): array
    {
        $this->notices = [];
        $client = app(PublicJobPageClient::class); $parser = app(PublicJobParser::class);
        $queries = array_values(array_filter(array_map('trim', explode(',', $keywords))));
        if (!$queries) $queries = config('services.public_jobs.queries', ['software', 'internship', 'data', 'engineering']);
        $pages = max(1, min(8, (int)config('services.public_jobs.pages', 3)));
        $jobs = []; $details = 0; $restricted = false;
        $limit = max(0, min(400, (int)config('services.public_jobs.detail_limit', 150)));
        // Hiredly's public SSR catalogue exposes page numbers, independent of the user's app search.
        $requests = $this->name === 'hiredly' ? [['', max($pages, min(12, $pages*3))]] : array_map(fn ($q)=>[$q,$this->name==='jobstreet'?1:$pages],array_slice($queries,0,12));
        // A scheduled/full sync also sweeps each state's public page; a targeted keyword refresh does not.
        if (!$keywords && in_array($this->name, ['ricebowl', 'jobstore'], true)) {
            $regionPages = max(1, min(5, (int) config('services.public_jobs.region_pages', 2)));
            $regionQueries = $this->name === 'jobstore' ? [''] : config('services.public_jobs.region_queries', ['']);
            foreach (config('services.public_jobs.regions', []) as $region) {
                foreach ($regionQueries as $query) $requests[] = [$query, $query === '' ? $regionPages : 1, $region];
            }
        }
        foreach ($requests as $request) {
            [$query, $pageCount] = $request;
            $region = $request[2] ?? null;
            for ($page=1; $page<=$pageCount; $page++) {
                $url = $this->url($query, $page, $region);
                try {
                    $html = $client->get($url);
                    $rows = $parser->parse($this->name, $html);
                    if (!$rows) {
                        if (!preg_match('/no (?:matching )?(?:jobs|results)|0 jobs|no vacancies/i', strip_tags($html))) $this->notices[] = 'The public page format was not recognised; no listings were invented.';
                        break;
                    }
                    $added = 0;
                    foreach ($rows as $row) {
                        if (isset($jobs[$row['external_id']])) continue;
                        // A state page vouches for the state when the card names only a small town.
                        if ($region && !MalaysiaLocation::stateOf($row['location'] ?? '') && ($state = MalaysiaLocation::stateOf(str_replace('-', ' ', $region)))) {
                            $row['location'] = trim(($row['location'] ?? '') !== '' ? $row['location'].', '.$state : $state);
                        }
                        if (!app(JobCatalogue::class)->eligibleArray($row, $this->name)) continue;
                        // Summary-only cards remain unscored unless a full public description can be obtained.
                        if (!$restricted && in_array($this->name,['ricebowl','maukerja'],true) && $details < $limit) {
                            $details++;
                            if (!app()->runningUnitTests()) usleep(200_000);
                            try { $row = $parser->detail($client->get($row['application_url']), $row); }
                            catch (\Throwable $error) {
                                $restricted = in_array($error->getCode(), [401, 403, 429], true);
                                $this->notices[] = $restricted ? $error->getMessage()
                                    : 'Some full descriptions were unavailable; those results use summaries without a fit score.';
                            }
                        }
                        if ($row['expires_at'] && \Carbon\Carbon::parse($row['expires_at'])->isPast()) continue;
                        $jobs[$row['external_id']] = $row; $added++;
                    }
                    if ($restricted) break 2; // Keep the page already fetched; make no further requests to a restricted source.
                    if (!$added && $page>1) break;
                    if (!app()->runningUnitTests()) usleep(300_000);
                } catch (\Throwable $error) {
                    if (!$jobs) throw $error;
                    $this->notices[] = $error->getMessage();
                    break 2; // Keep completed pages; do not retry a source restriction repeatedly.
                }
            }
        }
        if (!$jobs && $this->notices) throw new RuntimeException($this->notices[0]);
        return array_values($jobs);
    }

    private function url(string $query, int $page, ?string $region = null): string
    {
        $slug = Str::slug($query) ?: 'jobs';
        if ($region) {
            return match($this->name) {
                'ricebowl' => 'https://www.ricebowl.my/jobsearch/'.($query === '' ? '' : $slug.'-').'jobs-in-'.$region.'?page='.$page,
                'jobstore' => 'https://www.jobstore.com/my/jobs/l-'.$region.'.html?page='.$page,
            };
        }
        return match($this->name) {
            'hiredly' => 'https://my.hiredly.com/jobs?page='.$page,
            'ricebowl' => 'https://www.ricebowl.my/jobsearch/'.$slug.'-jobs?page='.$page,
            'maukerja' => 'https://www.maukerja.my/en/jobsearch/'.$slug.'-jobs?page='.$page,
            'jobstore' => 'https://www.jobstore.com/my/jobs/q-'.$slug.'.html?page='.$page,
            // Use public search pages only; no private API, cookies, captcha solver or proxy bypass.
            'jobstreet' => 'https://my.jobstreet.com/'.$slug.'-jobs',
        };
    }
}
