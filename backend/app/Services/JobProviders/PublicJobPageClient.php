<?php

namespace App\Services\JobProviders;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PublicJobPageClient
{
    public const HOSTS = ['my.hiredly.com', 'www.ricebowl.my', 'www.maukerja.my', 'www.jobstore.com', 'my.jobstreet.com'];

    public function get(string $url): string
    {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !in_array(parse_url($url, PHP_URL_HOST), self::HOSTS, true)
            || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_PORT)) {
            throw new RuntimeException('The listing URL is outside the supported public sources.');
        }

        return Cache::remember('public-job-page:'.hash('sha256', $url), now()->addMinutes(30), function () use ($url) {
            $response = Http::withHeaders(['Accept' => 'text/html', 'User-Agent' => 'SkillsCareer/1.0 (public job listings)'])
                ->connectTimeout(5)->timeout(15)->withOptions(['allow_redirects' => false])->get($url);
            if (in_array($response->status(), [401, 403, 429], true)) {
                throw new RuntimeException('Public page access is restricted (HTTP '.$response->status().'). Open the source website or use an approved data feed.', $response->status());
            }
            if (!$response->successful()) throw new RuntimeException('The public source returned HTTP '.$response->status().'.');
            $body = $response->body();
            if (strlen($body) > 6_000_000) throw new RuntimeException('The public page exceeds the supported size.');
            if (preg_match('/<title[^>]*>\s*(Just a moment|Access denied|Robot Check)/i', $body)) {
                throw new RuntimeException('The source requires browser verification; automatic collection was stopped.', 403);
            }
            return $body;
        });
    }
}
