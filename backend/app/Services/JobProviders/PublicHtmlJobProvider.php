<?php

namespace App\Services\JobProviders;

use App\Contracts\JobProvider;
use App\Services\JobCatalogue;
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
        $limit = max(0, min(100, (int)config('services.public_jobs.detail_limit', 24)));
        // Hiredly's public SSR catalogue exposes page numbers, independent of the user's app search.
        $requests = $this->name === 'hiredly' ? [['', max($pages, min(12, $pages*3))]] : array_map(fn ($q)=>[$q,$this->name==='jobstreet'?1:$pages],array_slice($queries,0,8));
        foreach ($requests as [$query,$pageCount]) {
            for ($page=1; $page<=$pageCount; $page++) {
                $url = $this->url($query, $page);
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
                        if (!app(JobCatalogue::class)->eligibleArray($row, $this->name)) continue;
                        // Summary-only cards remain unscored unless a full public description can be obtained.
                        if (!$restricted && in_array($this->name,['ricebowl','maukerja'],true) && $details < $limit) {
                            $details++;
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

    private function url(string $query, int $page): string
    {
        $slug = Str::slug($query) ?: 'jobs';
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
